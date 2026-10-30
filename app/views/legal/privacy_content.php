<?php
/**
 * Política de Privacidade do Kolae (texto em português, D42), conforme a LGPD (Lei 13.709/2018).
 * Cada item vira uma seção com âncora. Ao mudar o texto, atualizar LegalController::UPDATED_AT.
 */
$email = '<a href="mailto:' . $contactEmail . '">' . $contactEmail . '</a>';

return [
    ['quem-somos', 'Quem somos', '
        <p>O Kolae é um projeto acadêmico da FATEC Ferraz de Vasconcelos que conecta atletas a quadras esportivas particulares, pelo site <a href="' . BASE_URL . '/">kolae.online</a> e pelo aplicativo. A equipe do Kolae é a controladora dos dados pessoais tratados na plataforma. Contato: ' . $email . '.</p>'],

    ['dados', 'Dados que coletamos', '
        <ul>
            <li><strong>Conta:</strong> nome, e-mail, data de nascimento e senha. A senha é guardada de forma cifrada (hash) e ninguém da equipe consegue vê-la.</li>
            <li><strong>Foto de perfil:</strong> opcional, enviada por você.</li>
            <li><strong>Responsáveis por quadra:</strong> CNPJ (guardado criptografado), número de WhatsApp e os dados das quadras: endereço e coordenadas no mapa, fotos, preço, descrição, esportes, horário de funcionamento e a agenda de partidas, que pode incluir o nome de quem alugou o horário, se o responsável quiser anotar.</li>
            <li><strong>Localização no aplicativo:</strong> quando você permite, a posição do celular é usada só para buscar quadras próximas e calcular distâncias. Ela não é gravada.</li>
            <li><strong>Dados técnicos:</strong> registros de acesso ao servidor (como endereço IP, data e hora) e um cookie de sessão para manter você conectado no site.</li>
        </ul>'],

    ['uso', 'Para que usamos os dados', '
        <ul>
            <li>criar e manter a sua conta e permitir o login no site e no aplicativo;</li>
            <li>conferir a idade mínima de 18 anos;</li>
            <li>mostrar as quadras, o status, o horário e a agenda;</li>
            <li>permitir o contato com o responsável pela quadra, pelo WhatsApp;</li>
            <li>enviar o e-mail de recuperação de senha quando você pedir;</li>
            <li>manter a plataforma segura e prevenir abusos.</li>
        </ul>
        <p>Não usamos seus dados para publicidade e não os vendemos.</p>'],

    ['bases-legais', 'Bases legais', '
        <p>Tratamos dados pessoais com base na LGPD (art. 7º): para executar o serviço que você pediu ao criar a conta, para cumprir obrigações legais, pelo nosso legítimo interesse em manter a plataforma segura e funcionando e, no caso da foto de perfil e da localização no aplicativo, pelo seu consentimento, que pode ser retirado a qualquer momento.</p>'],

    ['publico', 'O que fica visível para outras pessoas', '
        <ul>
            <li><strong>Quadras:</strong> nome, endereço, fotos, preço, descrição, esportes, horário, status e os horários ocupados da agenda.</li>
            <li><strong>Responsável pela quadra:</strong> nome, foto de perfil, data em que entrou no Kolae e o número de WhatsApp, para o botão "Falar com o dono".</li>
            <li><strong>Nunca aparecem publicamente:</strong> e-mail, data de nascimento, CNPJ, senha e o nome de quem alugou cada horário.</li>
        </ul>'],

    ['compartilhamento', 'Com quem compartilhamos', '
        <p>Compartilhamos dados apenas com quem nos ajuda a operar a plataforma, na medida do necessário: o provedor de hospedagem do site e do banco de dados, o serviço de mapas do Google (para localizar endereços e exibir o mapa) e o serviço de envio de e-mails. Também podemos compartilhar dados com autoridades quando a lei exigir.</p>'],

    ['cookies', 'Cookies e armazenamento no navegador', '
        <p>Usamos um cookie de sessão para manter você conectado no site e guardamos no seu navegador as preferências de tema (claro ou escuro) e de idioma. Não usamos cookies de publicidade nem de rastreamento.</p>'],

    ['retencao', 'Por quanto tempo guardamos', '
        <p>Guardamos os dados enquanto a sua conta existir. Quando a conta é excluída, apagamos ou anonimizamos os dados, exceto os que a lei nos obrigar a manter por algum tempo, como registros de acesso.</p>'],

    ['seguranca', 'Segurança', '
        <p>Usamos conexão segura (HTTPS), senhas cifradas, CNPJ criptografado e acesso restrito ao banco de dados. Nenhum sistema é totalmente imune a falhas; se acontecer um incidente que possa afetar você, avisaremos.</p>'],

    ['direitos', 'Seus direitos', '
        <p>Pela LGPD (art. 18), você pode pedir: a confirmação de que tratamos seus dados; o acesso a eles; a correção de dados incompletos ou errados; a anonimização, o bloqueio ou a exclusão de dados desnecessários; a portabilidade; informações sobre com quem compartilhamos; e a retirada do consentimento.</p>
        <p>Nome, foto e WhatsApp podem ser alterados no seu perfil. Para os demais pedidos, escreva para ' . $email . '. Respondemos em até 15 dias. Você também pode procurar a Autoridade Nacional de Proteção de Dados (ANPD).</p>'],

    ['menores', 'Menores de idade', '
        <p>O Kolae é destinado a maiores de 18 anos. Não aceitamos cadastro de menores; se identificarmos uma conta de menor, ela será excluída.</p>'],

    ['alteracoes', 'Alterações desta política', '
        <p>Esta política pode ser atualizada. A data da versão atual aparece no topo desta página. Se a mudança for importante, avisaremos no site ou no aplicativo.</p>'],
];
