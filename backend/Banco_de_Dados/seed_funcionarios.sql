-- Seed: funcionários de desenvolvimento
--
--  Usuários:
--    admin@automax.com.br  / automax123   (gerente)
--    recepcao@automax.com.br / recepcao123 (recepcao)
--    mecanico@automax.com.br / mecanico123 (mecanico)
--
--  NUNCA use estas senhas em produção.

USE oficina_db;

INSERT INTO funcionarios (nome_funcionario, email, nivel_de_acesso, senha)
VALUES
    (
        'Administrador Automax',
        'admin@automax.com.br',
        'gerente',
        '$2y$12$vgUvZLzh/O4FAZhU4vksD.NK5zxDZGtr3LrpPomA2iSr.iskVUEEW'
    ),
    (
        'Recepção Automax',
        'recepcao@automax.com.br',
        'recepcao',
        '$2y$12$P6LzZ6Upap.mjLQRSELpsu456H24EgZ6IV1y5EECaWbMIi2cByGJu'
    ),
    (
        'Mecânico Automax',
        'mecanico@automax.com.br',
        'mecanico',
        '$2y$12$ZSNimeP7JGpG6bkCO3kio.BvpmGrj4ElbJuHn3wEm8SxeMYs50K7O'
    )
ON DUPLICATE KEY UPDATE
    nome_funcionario = VALUES(nome_funcionario),
    nivel_de_acesso  = VALUES(nivel_de_acesso);
