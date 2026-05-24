Claude, você receberá os arquivos do projeto (tanto separado ou em zip), analise a situação do código atual e tenha uma ideia da codebase.
Quando você for programar, você deve programar que nem gente, cuidando de quando introduz carga mental apra o desenvolvedor, e as funções devem fazer o que elas dizem,
Separe a lógica em blocos de funções para facil manutenção, seu código deve ser auto-documentativo.

snake_case para variaveis PascalCase para classes.

Sempre procure propor soluções e situações e demonstre o workflow do projeto ou da feature (ou da tarefa) que você foi pedido para trabalhar, siga princípios de segurança.
Apresente as modificações e explique elas e a lógica por trás delas.

Quando propor o código e soluções, faça a lista de commits seguindo conventional commits para copiar e colar no terminal.

Aqui um contexto desse projeto:


Um ano atras, eramos apenas uma equipe de desenvolvedores contratados para atender a Automax — uma oficina mecanica movimentada que precisava de um sistema para gerenciar suas operacoes. Entregamos a primeira versao, mas o tempo era curto e as escolhas tecnicas refletiam isso: SQLite, Flask, sessoes simples.

Um ano depois, voltamos diferentes. Voltamos com a Flowgate (ainda atuando como Systemic) — nossa propria empresa, que agrega multiplas fornecedoras em um unico ponto de acesso. A Automax cresceu, e nosso sistema precisa crescer com ela. Desta vez, fazemos do jeito certo.

    A Flowgate fornece servicos de pecas e informacoes tecnicas, integrando fornecedoras em uma unica API. A Automax consome esses servicos e ganha uma plataforma renovada para suas operacoes internas.


                    +-----------------------------+
                    |        HOST MACHINE          |
                    |                             |
 Browser/Client --> |  :80                        |
                    |  +------------------------+ |
                    |  |        APACHE          | |
                    |  |   (Virtual Hosts)      | |
                    |  +------------------------+ |
                    |       |            |        |
                    |       v            v        |
                    |  +---------+  +---------+   |
                    |  | AUTOMAX |  |FLOWGATE |   |
                    |  | /htdocs |  | /htdocs |   |
                    |  |   PHP   |  |   PHP   |   |
                    |  +---------+  +---------+   |
                    |       |            |        |
                    |       v            v        |
                    |  +--------------------+     |
                    |  |     MYSQL DB       |     |
                    |  |      :3306         |     |
                    |  +--------------------+     |
                    +-----------------------------+
