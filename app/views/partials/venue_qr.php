<?php
/**
 * QR Code da quadra (D54): janela com um cartaz pronto para imprimir (faixa azul do Kolae,
 * nome da quadra, QR para a página pública, chamada e endereço) e o botão de baixar em PNG.
 * O QR é gerado no navegador (qrcode-generator, cdnjs): sem biblioteca no servidor.
 * Abre por qualquer botão com data-qr-open. Usado na Visão geral.
 *
 * Variáveis: $lang, $venue, $publicUrl (URL absoluta da página pública).
 */
$qe = fn($s) => htmlspecialchars((string)$s);
$qrData = [
    'url' => $publicUrl . '?ref=qr', // visita pelo QR conta como origem "QR Code" nas estatísticas (D62)
    'name' => $venue['name'],
    'cta' => $lang['qr_poster_cta'],
    'file' => 'kolae-qr-' . (int)$venue['id'] . '.png',
];
?>
<div id="qr-modal" class="hidden fixed inset-0 z-[60] bg-black/60 items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="qr-title">
    <div class="w-full max-w-md bg-surface-elevated text-content-primary rounded-2xl shadow-2xl p-5 sm:p-6 max-h-[calc(100vh-2rem)] overflow-y-auto">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="qr-title" class="text-lg font-bold"><?php echo $lang['qr_title']; ?></h2>
                <p class="mt-1 text-sm text-content-secondary"><?php echo $lang['qr_text']; ?></p>
            </div>
            <button type="button" id="qr-close" class="w-10 h-10 shrink-0 rounded-full hover:bg-surface-secondary flex items-center justify-center text-lg" aria-label="<?php echo $qe($lang['overview_close']); ?>"><i class="fas fa-xmark"></i></button>
        </div>
        <canvas id="qr-canvas" width="900" height="1200" class="mt-5 w-full h-auto rounded-xl border border-content-secondary/15 bg-white" role="img" aria-label="<?php echo $qe($lang['qr_title'] . ': ' . $venue['name']); ?>"></canvas>
        <div class="mt-5 flex flex-col sm:flex-row gap-3">
            <a id="qr-download" href="#" download="<?php echo $qe($qrData['file']); ?>" class="flex-1 inline-flex items-center justify-center gap-2 bg-brand-500 hover:bg-brand-600 text-white font-semibold py-3 rounded-xl transition-colors">
                <span><i class="fas fa-download"></i></span><?php echo $lang['qr_download']; ?>
            </a>
            <a href="<?php echo $qe($publicUrl); ?>" target="_blank" rel="noopener" class="flex-1 inline-flex items-center justify-center gap-2 border border-content-secondary/20 hover:border-brand-500 font-semibold py-3 rounded-xl transition-colors">
                <span><i class="fas fa-arrow-up-right-from-square"></i></span><?php echo $lang['overview_public_page']; ?>
            </a>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<script>
    (function () {
        const data = <?php echo json_encode($qrData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const modal = document.getElementById('qr-modal');
        const canvas = document.getElementById('qr-canvas');
        const download = document.getElementById('qr-download');
        const closeBtn = document.getElementById('qr-close');
        let opener = null;
        let drawn = false;

        // Quebra o texto em linhas que cabem na largura
        const lines = (ctx, text, max) => {
            const out = [];
            let line = '';
            text.split(' ').forEach(word => {
                const test = line ? line + ' ' + word : word;
                if (ctx.measureText(test).width > max && line) { out.push(line); line = word; } else { line = test; }
            });
            if (line) out.push(line);
            return out;
        };

        const draw = () => {
            const ctx = canvas.getContext('2d');
            const W = canvas.width, H = canvas.height;
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, W, H);

            // Faixa azul com o nome do Kolae
            ctx.fillStyle = '#0084FF';
            ctx.fillRect(0, 0, W, 150);
            ctx.fillStyle = '#ffffff';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = '600 72px Kanit, sans-serif';
            ctx.fillText('Kolae', W / 2, 78);

            // Nome da quadra (até 2 linhas)
            ctx.fillStyle = '#0b1220';
            ctx.font = '600 56px Kanit, sans-serif';
            let y = 230;
            lines(ctx, data.name, W - 120).slice(0, 2).forEach(l => { ctx.fillText(l, W / 2, y); y += 66; });

            // QR
            const qr = qrcode(0, 'M');
            qr.addData(data.url);
            qr.make();
            const count = qr.getModuleCount();
            const size = 560;
            const cell = Math.floor(size / count);
            const qrSize = cell * count;
            // Posição em pixel inteiro: com meio pixel o navegador suaviza as bordas e
            // aparecem linhas brancas entre os quadradinhos (atrapalha a leitura)
            const x0 = Math.round((W - qrSize) / 2);
            const y0 = Math.round(y + 20);
            ctx.fillStyle = '#0b1220';
            for (let r = 0; r < count; r++) {
                for (let c = 0; c < count; c++) {
                    if (qr.isDark(r, c)) ctx.fillRect(x0 + c * cell, y0 + r * cell, cell, cell);
                }
            }

            // Chamada e endereço
            ctx.font = '500 38px Poppins, sans-serif';
            let ty = y0 + qrSize + 70;
            lines(ctx, data.cta, W - 140).slice(0, 2).forEach(l => { ctx.fillText(l, W / 2, ty); ty += 50; });
            ctx.fillStyle = '#0069CC';
            ctx.font = '600 30px Poppins, sans-serif';
            ctx.fillText(data.url.replace(/^https?:\/\//, ''), W / 2, Math.min(ty + 20, H - 50));

            download.href = canvas.toDataURL('image/png');
            drawn = true;
        };

        const open = btn => {
            opener = btn;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            closeBtn.focus();
            // Espera as fontes da página para o cartaz sair com a identidade do Kolae
            if (!drawn) (document.fonts ? document.fonts.ready : Promise.resolve()).then(draw);
        };
        const close = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (opener) opener.focus();
        };

        document.querySelectorAll('[data-qr-open]').forEach(btn => btn.addEventListener('click', () => open(btn)));
        closeBtn.addEventListener('click', close);
        modal.addEventListener('click', e => { if (e.target === modal) close(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) close(); });
    })();
</script>
