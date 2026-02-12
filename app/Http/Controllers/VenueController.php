<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venue;
use App\Models\Address;
use App\Models\VenueImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;


class VenueController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            //admin tem permissao de ver tudo
            $venues = Venue::with('address', 'images')->get();
        } else {
            //usuario ve so as suas
            $venues = $user->venue()->with('address', 'images')->get();
        }

        return view('venues.index', compact('venues'));
    }

    public function create()
    {
        $this->checkCnpjStatus();
        return view('venues.create');
    }

    public function store(Request $request)
    {
        $this->checkCnpjStatus();

        $validate = $request->validate([
            //Address
            'cep' => 'required|string|max:9',
            'street' => 'required|string|max:255',
            'number' => 'required|string|max:10',
            'neighborhood' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|size:2',
            'complement' => 'nullable|string|max:255',

            //Venue
            'name' => 'required|string|max:255',
            'average_price_per_hour' => 'nullable|numeric',
            'court_capacity' => 'nullable|integer',
            'floor_type' => 'nullable|string',
            'has_leisure_area' => 'boolean',
            'has_lighting' => 'boolean',
            'is_covered' => 'boolean',

            // Imagens
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048' // Máx 2MB por foto
        ]);
        //Intregacao Geolocalizacao
        $lat = null;
        $lng = null;

        $addressString = "{$request->street}, {$request->number}, {$request->neighborhood}, {$request->city}, {$request->state}, Brazil";

        try{
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json',[
                'address' => $addressString,
                'key' => env('GOOGLE_MAPS_API_KEY'),
             ]);

             if ($response->successful() && !empty($response['results'])) {
                $location = $response['results'][0]['geometry']['location'];
                $lat = $location['lat'];
                $lng = $location['lng'];
            }
        }catch(\Exception $e){
            //Se a API falhar na quebra, apenas salva sem lat e lng
        }

        //Criar Endereco
        $address = Address::create($request->only([
            'cep' => $request->cep,
            'street' => $request->street,
            'number' => $request->number,
            'neighborhood' => $request->neighborhood,
            'city' => $request->city,
            'state' => $request->state,
            'complement' => $request->complement,
            'latitude' => $lat,
            'longitude' => $lng,
        ]));

        //Criar quadra
        $venue = Venue::create([
            'user_id' => Auth::id(),
            'address_id' => $address->id,
            'name' => $request->name,
            'average_price_per_hour' => $request->average_price_per_hour,
            'court_capacity' => $request->court_capacity,
            'floor_type' => $request->floor_type,
            'has_leisure_area' => $request->boolean('has_leisure_area'),
            'leisure_area_capacity' => $request->leisure_area_capacity,
            'has_lighting' => $request->boolean('has_lighting'),
            'is_covered' => $request->boolean('is_covered'),
            'status' => 'available'
        ]);

        //Upload de foto
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                //salva em storage/app/public/venues
                $path = $image->store('venues', 'public');

                VenueImage::create([
                    'venue_id' => $venue->id,
                    'file_path' => $path
                ]);
            }
        }
        return redirect()->route('venues.index')->with('success', 'Quadra Cadastrada!');
    }

    //Editar quadra
    public function edit(Venue $venue)
    {
        //Laravel ja busca o id automaticamente
        $this->authorizeAction($venue);
        return view('venues.edit', compact('venue'));
    }

    //Atualizar quadra
    public function update(Request $request, Venue $venue)
    {
        $this->authorizeAction($venue);

        //Validacao
        $request->validate([
            'name' => 'required|string|max:255',
            'cep' => 'required|string|max:9',
            'street' => 'required|string|max:255',
            'number' => 'required|string|max:10',
            'neighborhood' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|size:2',
            'average_price_per_hour' => 'nullable|numeric',
            'court_capacity' => 'nullable|integer',
            'floor_type' => 'nullable|string',
            'has_leisure_area' => 'boolean',
            'has_lighting' => 'boolean',
            'is_covered' => 'boolean',
        ]);
        //Atualizar endereco
        $venue->address->update($request->only([
            'cep', 'street', 'number', 'neighborhood', 'city', 'state', 'complement'
        ]));

         //Atualizar quadra
         $venue->update($request->only([
            'name', 'average_price_per_hour', 'court_capacity', 'floor_type'
            // Use $request->boolean() para checkboxes se necessário
        ]));

        // Deletar Imagens Selecionadas
        if ($request->has('delete_images')) {
            foreach ($request->delete_images as $imageId) {
                $image = VenueImage::find($imageId);
                if ($image && $image->venue_id == $venue->id) {
                    Storage::disk('public')->delete($image->file_path); // Apaga do disco
                    $image->delete(); // Apaga do banco
                }
            }
        }

        // Upload de Novas Imagens
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('venues', 'public');
                VenueImage::create([
                    'venue_id' => $venue->id,
                    'file_path' => $path
                ]);
            }
        }

        return redirect()->route('venues.index')->with('success', 'Quadra atualizada!');

    }

    //Deletar quadra
    public function destroy(Venue $venue)
    {
        $this->authorizeAction($venue);

        // Apagar as imagens do disco antes de deletar o registro
        foreach ($venue->images as $image) {
            Storage::disk('public')->delete($image->file_path);
        }

        $venue->delete(); // O endereço e imagens somem do banco por causa do 'onDelete cascade' na migration

        return redirect()->route('venues.index')->with('success', 'Quadra removida com sucesso.');
    }

    //--METODOS AUXILIARES--

    //Verifica seguraca

    private function authorizeAction(Venue $venue)
    {
        $user = Auth::user();
        if ($venue->user_id !== $user->id && $user->role !== 'admin'){
            abort(403, 'Acesso não autorizado');
        }
    }

    //Verifica CNPJ obrigatorio para usuarios comuns
    private function checkCnpjStatus()
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && empty($user->cnpj)){
            //Redireciona para o perfil com erro
            abort(403, 'Você precisar cadastrar um CNPJ para gerenciar quadras.');
        }
    }
}