# DocuSign PHP JWT Example
DocuSign PHP Example get an access token with JWT Grant authentication and get accessCode with DocuSign API

## Installation Demo/Developer Account
### Create new app
<ol>
<li>Login to developer account, Link : https://developers.docusign.com/auth/docusign-demo</li>
<li>Go to "My Apps & Keys", Link : https://admindemo.docusign.com/api-integrator-key</li>
<li>Note: My Account Information
<b>Account ID:</b> xxxxxxxxxxxx<br />
<b>API Username:</b> 11111111-1234-5678-bc9d-xxxxxxxxxxxx<br />
<b>API Account ID:</b> 2222222-1234-5678-99ca-xxxxxxxxxxxx<br />
<b>Account's Base URI:</b> https://demo.docusign.net</li>
<li>Click the "ADD APP & INTEGRATION KEY" button to create a new app.</li>
<li>Assign a new app name and click the "ADD" button.</li>
<li>Note: Integration Key<br />
<b>Client ID:</b> 33333333-1234-5678-9579-xxxxxxxxxxxx</li>
<li>Click "ADD RSA KEYPAIR" button, Note :<br />
Keypair ID: 4444444-8ac0-4203-9d3c-xxxxxxxxxxxx<br />
<b>Public Key:</b><br />
-----BEGIN PUBLIC KEY-----<br />
...... 55555555<br />
-----END PUBLIC KEY-----<br />
<b>Private Key:</b><br />
-----BEGIN RSA PRIVATE KEY-----<br />
...... 66666666<br />
-----END RSA PRIVATE KEY-----</li>
<li>Click the "ADD URI" button and assign Redirect URIs: http://localhost:8080/callback.php</li>
<li>Click the "SAVE" button.</li>
</ol>

### Prepare callback.php file and accept permission (Developer Account)
<ol>
<li>Extract docusign-php-jwt.zip file.
Clone Project from GitHub : https://github.com/donotdont/docusign-php-jwt/tree/master</li>
<li>Set configuration all parameters in callback.php .</li>
<li>Replace <b>client_id</b> parameter in index.html .<br />
client_id=33333333-1234-5678-9579-xxxxxxxxxxxx</li>
<li>Open Command Prompt, go to current code directory and start php server by this command:<br />
<code>cd C:\xampp\htdocs\docusign && php -S localhost:8080</code></li>
<li>Open browser, goto http://localhost:8080 and click "Login to accept permission first time" link.</li>
<li>Login demo account.</li>
<li>Click the "ACCEPT" button.</li>
<li>Check and debug response from Docusign.</li>
</ol>

### Review the app, upload to server and run the code by Cron job
### Request App Review
- [20 API calls in the last 30 days.]
- [Successfully completed the last 20 API calls.]
- [Complies with DocuSign API rules and limits.]
<ol>
<li>Upload callback.php to server.</li>
<li>Set run the code every 20 minute run by Cron job with this command : <br />
<code>ubuntu@os:~$ crontab -e</code></li>
<li>Add one command line to Crontab<br />
<code>*/20 * * * * /usr/bin/php /home/ubuntu/os/callback.php >> /home/ubuntu/os/callback.log 2>&1</code></li>
<li>Save and check crontab to run the process.<br />
<code>ubuntu@os:~$ crontab -l</code></li>
<li>Waiting total requests more than 20.</li>
<li>Go to the app on Docusign.</li>
<li>Click the "SUBMIT FOR REVIEW" button.</li>
<li>Waiting review passed about 20 minute.</li>
</ol>

### Live in Production
<ol>
<li>Click the "PROMOTE TO PRODUCTION" button.</li>
<li>Click the checkbox and "SUBMIT" button to confirm terms and conditions.</li>
<li>Login with production account.</li>
<li>Select the Production account and click the "SELECT" button.</li>
<li>Waiting the process 1-3 day business by DocuSign team in Washington, USA.</li>
<li>Confirm DocuSign API GoLive Form on your email.</li>
</ol>

## Installation Production Account
### Manual install :
<ol>
<li>Prepare callback.php file and accept permission (Production Account) :<br />
Set configuration all parameters in callback.php .</li>
<li>Replace <b>client_id</b> parameter in index.html .<br />
client_id=33333333-1234-5678-9579-xxxxxxxxxxxx</li>
<li>Open Command Prompt, go to current code directory and start php server by this command: <br />
<code>cd C:\xampp\htdocs\docusign && php -S localhost:8080</code></li>
<li>Open browser, goto http://localhost:8080 and click "Login to accept permission first time" link.</li>
<li>Login production account.</li>
<li>Click the "ACCEPT" button.</li>
<li>Check and debug response from Docusign.</li>
</ol>
