<?php
/**
 * Termos de Uso do Kolae (texto em português, D42). Cada item vira uma seção com âncora.
 * Ao mudar o texto, atualizar LegalController::UPDATED_AT.
 */
$email = '<a href="mailto:' . $contactEmail . '">' . $contactEmail . '</a>';

return [
    ['sobre', 'O que é o Kolae', '
        <p>O Kolae é uma plataforma, com site e aplicativo, que ajuda atletas a encontrar quadras esportivas particulares. Ele mostra onde ficam as quadras, se estão disponíveis, em uso ou fechadas, o horário de funcionamento, a agenda do dia e o contato do responsável por cada quadra, pelo WhatsApp ou por mensagens no próprio Kolae.</p>
        <p>O Kolae é um projeto acadêmico, desenvolvido como Projeto Integrador do curso da FATEC Ferraz de Vasconcelos.</p>'],

    ['aceite', 'Aceite destes termos', '
        <p>Ao criar uma conta ou usar o Kolae, você concorda com estes Termos de Uso e com a <a href="' . BASE_URL . '/privacidade">Política de Privacidade</a>. Se não concordar, não use a plataforma.</p>'],

    ['conta', 'Cadastro e conta', '
        <ul>
            <li>Para criar uma conta é preciso ter <strong>18 anos ou mais</strong>. A data de nascimento informada no cadastro não pode ser alterada depois.</li>
            <li>Os dados do cadastro precisam ser verdadeiros e estar atualizados.</li>
            <li>A senha é pessoal. Você é responsável pelo que for feito com a sua conta; se suspeitar de uso indevido, troque a senha e fale com a gente.</li>
        </ul>'],

    ['responsaveis', 'Responsáveis por quadra', '
        <p>Quem cadastra um CNPJ passa a poder cadastrar quadras. O responsável por quadra se compromete a:</p>
        <ul>
            <li>informar dados corretos sobre a quadra: endereço, preço, piso, comodidades, esportes e descrição;</li>
            <li>publicar apenas fotos que tenha o direito de usar;</li>
            <li>manter o horário de funcionamento e a agenda de partidas atualizados, porque é deles que sai o status mostrado no aplicativo;</li>
            <li>cadastrar um número de WhatsApp para contato. Esse número aparece publicamente no aplicativo, no botão "Falar com o dono".</li>
        </ul>'],

    ['premium', 'Kolae Premium', '
        <p>Responsáveis por quadra podem ter o plano <strong>Kolae Premium</strong>, que vale para todas as quadras da conta e dá acesso às estatísticas completas e ao anúncio da quadra. Nesta versão, o plano é ativado pela equipe do Kolae, com uma data de validade; vencida a validade, a conta volta ao plano grátis.</p>
        <ul>
            <li>Os anúncios aparecem no site e no aplicativo sempre com a etiqueta <strong>"Patrocinado"</strong>.</li>
            <li>O responsável responde pelo conteúdo do anúncio, que precisa ser verdadeiro e seguir estes termos (por exemplo, não prometer um desconto que a quadra não oferece).</li>
            <li>A equipe do Kolae pode remover um anúncio que descumpra estes termos, informando o motivo ao responsável.</li>
            <li>Com o plano vencido, o anúncio fica guardado, mas deixa de aparecer.</li>
        </ul>'],

    ['mensagens', 'Mensagens pelo Kolae', '
        <p>Quem tem conta pode mandar mensagens, pelo aplicativo, ao responsável por uma quadra, que responde pelo painel do site. Há uma conversa por pessoa e por quadra.</p>
        <ul>
            <li>As mensagens são só de texto, com até 1000 caracteres, e <strong>não podem ter links nem e-mails</strong>. Para evitar abusos, cada conta pode enviar até 20 mensagens por minuto.</li>
            <li>Use as mensagens para tirar dúvidas e combinar o aluguel. Valem as regras de "Uso adequado": nada de ofensas, golpes, propaganda ou mensagens repetidas.</li>
            <li>O responsável pode bloquear uma conversa: a pessoa continua lendo o que já foi trocado, mas não envia mais nada. O bloqueio pode ser desfeito.</li>
            <li>Se a quadra for desativada, a conversa fica só para leitura.</li>
            <li>Como no aluguel, o Kolae não responde pelo que for combinado nas mensagens.</li>
        </ul>'],

    ['aluguel', 'Aluguel e pagamento', '
        <p>O Kolae <strong>não intermedeia o aluguel nem o pagamento</strong> das quadras. A reserva, o valor, a forma de pagamento, o cancelamento e as regras de uso são combinados diretamente entre o atleta e o responsável pela quadra, fora da plataforma.</p>
        <p>Por isso, o Kolae não responde por esses acordos, nem pelo estado das quadras ou pelo que acontecer nelas.</p>'],

    ['status', 'Status e informações das quadras', '
        <p>O status (Disponível, Em uso ou Fechada), o horário e a agenda são calculados a partir do que o responsável cadastrou. Eles têm caráter informativo e podem não refletir a situação real da quadra se não estiverem atualizados. Confirme com o responsável antes de ir até o local.</p>'],

    ['conduta', 'Uso adequado', '
        <p>Não é permitido:</p>
        <ul>
            <li>cadastrar informações falsas ou se passar por outra pessoa;</li>
            <li>publicar ou enviar conteúdo ofensivo, discriminatório, ilegal ou que viole direitos de terceiros, inclusive nas mensagens e nos anúncios;</li>
            <li>tentar acessar contas de outras pessoas, áreas restritas ou dados que não sejam seus;</li>
            <li>usar programas automáticos para coletar dados ou sobrecarregar a plataforma.</li>
        </ul>'],

    ['conteudo', 'Conteúdo que você envia', '
        <p>Fotos, descrições, anúncios, mensagens e demais informações que você envia continuam sendo seus. Ao publicá-los, você autoriza o Kolae a exibi-los no site e no aplicativo enquanto estiverem cadastrados. Você pode alterar ou remover esse conteúdo a qualquer momento.</p>'],

    ['suspensao', 'Suspensão e exclusão de conta', '
        <p>Contas que descumprirem estes termos podem ser suspensas ou excluídas, e quadras com informações falsas podem ser desativadas. Você também pode pedir a exclusão da sua conta pelo e-mail ' . $email . '.</p>'],

    ['disponibilidade', 'Disponibilidade', '
        <p>Por ser um projeto acadêmico, o Kolae pode passar por mudanças, ficar fora do ar ou ser encerrado sem aviso prévio. Não garantimos que a plataforma funcione sem interrupções ou erros.</p>'],

    ['responsabilidade', 'Limitação de responsabilidade', '
        <p>O Kolae não se responsabiliza por informações cadastradas por responsáveis de quadra, por acordos feitos entre usuários, nem por danos decorrentes do uso das quadras ou da indisponibilidade da plataforma, nos limites permitidos pela lei.</p>'],

    ['alteracoes', 'Alterações destes termos', '
        <p>Estes termos podem ser atualizados. A data da versão atual aparece no topo desta página. Se a mudança for importante, avisaremos no site ou no aplicativo.</p>'],

    ['contato', 'Lei aplicável e contato', '
        <p>Estes termos seguem as leis do Brasil. Dúvidas, sugestões ou pedidos: ' . $email . '.</p>'],
];
