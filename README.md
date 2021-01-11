# docusign-php-jwt
## Installation Production Account
### Manual install :
<ol>
<li>Prepare callback.php file and accept permission (Production Account) :
Set configuration all parameters in callback.php .</li>
<li>Replace client_id parameter in index.html .
client_id=0aec0000-1234-5678-bd99-xxxxxxxxxxxx</li>
<li>Open Command Prompt, go to current code directory and start php server by this command:
cd C:\xampp\htdocs\docusign && php -S localhost:8080</li>
<li>Open browser, goto http://localhost:8080 and click "Login to accept permission first time" link.</li>
<li>Login production account.</li>
<li>Click the "ACCEPT" button.</li>
<li>Check and debug response from Docusign.</li>
</ol>
