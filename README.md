# Sistema de Ponto - Won Academy

Um sistema simples e funcional para registro de ponto de colaboradores, desenvolvido em PHP puro. Permite marcações de entrada, saída e intervalos, com a funcionalidade adicional de registro via WhatsApp utilizando geolocalização. Inclui painéis dedicados para gestores e colaboradores.

## Funcionalidades Principais

* ✅ **Registro de Ponto:** Marcação de Entrada, Início de Almoço, Retorno do Almoço e Saída.
* 📱 **Registro via WhatsApp:** Colaboradores podem enviar sua geolocalização via WhatsApp para registrar o ponto. *(*Requer configuração de API/Gateway do WhatsApp)*.
* 👨‍💼 **Painel do Gestor:**
    * Visualização das folhas de ponto dos colaboradores.
    * Geração de PDF da folha de horas para impressão ou envio para assinatura.
* 👤 **Painel do Colaborador:**
    * Visualização dos próprios registros de ponto.
* 📄 **Relatórios:** Folha de ponto individual em formato PDF.

## Tecnologias Utilizadas

* **Backend:** PHP (Puro, estruturado em funções).
* **Frontend:** Baseado no template [SB Admin 2](https://startbootstrap.com/theme/sb-admin-2) (HTML, CSS, JavaScript/jQuery).
* **Gerenciador de Dependências:** Composer (para bibliotecas PHP como `vlucas/phpdotenv`).

## Instalação (Como Começar)

1.  **Clone o Repositório:**
    ```bash
    git clone [URL_DO_SEU_REPOSITORIO_AQUI] nome-da-pasta
    cd nome-da-pasta
    ```

2.  **Instale as Dependências PHP:**
    ```bash
    composer install --no-dev --optimize-autoloader
    ```
    *(Certifique-se de ter o [Composer](https://getcomposer.org/) instalado)*.

3.  **Configuração do Ambiente:**
    * Crie um arquivo `.env` na raiz do projeto.
    * Edite o arquivo `.env` com as configurações do seu ambiente:
        * Conexão com o banco de dados (Host, Nome do DB, Usuário, Senha).
        * Credenciais da API do WhatsApp (se aplicável).
        * Outras configurações específicas da aplicação.
    * **Observação Importante:** Um arquivo `.env` de exemplo e o workflow do N8N (para a integração com WhatsApp) **não estão inclusos neste repositório**. Eles são **materiais exclusivos para alunos** do portal Won Academy.

4.  **Banco de Dados:**
    * Importe o schema do banco de dados. (Você precisará fornecer um arquivo `.sql` com a estrutura das tabelas ou instruções para criá-las).
    * *Exemplo:* `mysql -u [usuario] -p [nome_db] < database.sql`

5.  **Servidor Web:**
    * Configure seu servidor web (Apache, Nginx) para apontar o `DocumentRoot` (ou `root`) para a pasta pública do projeto (geralmente a raiz do projeto ou uma subpasta `public/`).

## Aprendizado e Recursos Adicionais

🎓 **Aulas Completas:** A construção passo a passo deste sistema, incluindo a configuração detalhada do ambiente, a integração com WhatsApp via N8N e explicações sobre o código, está disponível no portal **[Won Academy](https://wonacademy.com.br/)**.

🔑 **Arquivos Exclusivos:** O arquivo `.env` de exemplo e o workflow do N8N são liberados **exclusivamente para alunos** dentro do portal Won Academy.

## Uso

1.  Acesse a URL configurada no seu navegador.
2.  Faça login com as credenciais de gestor ou colaborador.
3.  Para registro via WhatsApp, siga as instruções detalhadas disponíveis no portal Won Academy.

## Contribuição

Contribuições são bem-vindas! Se encontrar bugs ou tiver sugestões, por favor, abra uma issue ou envie um pull request.

## Licença

Este projeto é distribuído sob a licença [NOME_DA_LICENCA - Ex: MIT]. Veja o arquivo `LICENSE` para mais detalhes.

---

**Principais mudanças:**

* Adicionei a **Observação Importante** na seção de Configuração do Ambiente sobre o `.env` e o N8N.
* Criei a seção **Aprendizado e Recursos Adicionais** com o link para a Won Academy e a informação sobre os arquivos exclusivos.
* Ajustei a seção **Uso** para direcionar ao portal para detalhes do WhatsApp.

Não se esqueça de adicionar este `README.md` atualizado ao seu Git e fazer o push!