-- =====================================================================
-- Patch 010 — Chat entre a pessoa e o responsável pela quadra (D66)
-- =====================================================================
-- O que faz (tudo só se ainda não existir):
--   * conversations: uma conversa por pessoa e quadra; "lida" pela última mensagem lida de
--     cada lado; bloqueio pelo dono
--   * messages: as mensagens (só texto, até 1000 caracteres)
--
-- Quando aplicar na produção: ANTES do merge da PR "feat/chat-banco" (parte B do chat). As
-- partes seguintes (API e painel) não precisam de outro patch.
--
-- Como aplicar: phpMyAdmin > clicar no NOME DO BANCO do Kolae na lista da esquerda (o topo
-- da página mostra "Database: <nome>") > aba SQL > colar e executar.
-- Só cria as tabelas se o banco escolhido for o do Kolae (tem a tabela `venues`). Sem banco,
-- ou com outro banco escolhido (ex.: information_schema), só mostra o aviso e não muda nada.
-- Funciona no MySQL (5.7 e 8) e no MariaDB. Pode ser executado mais de uma vez.
-- =====================================================================

SET @banco := DATABASE();
SET @kolae := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @banco AND TABLE_NAME IN ('venues', 'users')) = 2;
SELECT IF(@banco IS NULL,
          'Nenhum banco selecionado: clique no banco do Kolae na lista da esquerda e rode de novo na aba SQL dele. Nada foi mudado.',
          IF(@kolae,
             CONCAT('Aplicando no banco ', @banco),
             CONCAT('O banco ', @banco, ' não é o do Kolae (não tem venues e users): clique no banco do Kolae na lista da esquerda e rode de novo. Nada foi mudado.'))) AS aviso;

SET @sql := IF(@kolae, 'CREATE TABLE IF NOT EXISTS `conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT ''Quem pergunta; o responsável é o dono da quadra (venues.user_id)'',
  `user_last_read_id` int(11) DEFAULT NULL COMMENT ''Última mensagem que a pessoa leu'',
  `owner_last_read_id` int(11) DEFAULT NULL COMMENT ''Última mensagem que o responsável leu'',
  `blocked_at` timestamp NULL DEFAULT NULL COMMENT ''O responsável bloqueou: a pessoa lê, mas não envia'',
  `last_message_at` timestamp NULL DEFAULT NULL COMMENT ''NULL = conversa ainda vazia (fica fora das listas)'',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_conversations_venue_user` (`venue_id`, `user_id`),
  KEY `idx_conversations_user` (`user_id`, `last_message_at`),
  KEY `idx_conversations_venue` (`venue_id`, `last_message_at`),
  CONSTRAINT `fk_conversations_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conversations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci', 'SELECT ''conversations: pulado (banco errado)'' AS passo');
PREPARE passo FROM @sql; EXECUTE passo; DEALLOCATE PREPARE passo;

SET @sql := IF(@kolae, 'CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `body` varchar(1000) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_messages_conversation` (`conversation_id`, `id`),
  KEY `idx_messages_sender` (`sender_id`, `created_at`),
  CONSTRAINT `fk_messages_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci', 'SELECT ''messages: pulado (banco errado)'' AS passo');
PREPARE passo FROM @sql; EXECUTE passo; DEALLOCATE PREPARE passo;

SELECT IF(@kolae, 'Patch 010 concluído', 'Patch 010 NÃO aplicado: escolha o banco do Kolae e rode de novo') AS resultado;
