Endomondo API base
============

Endomondo is not offering some official API but there is a way that allows you to put, read or delete some data through their new web API.

You can use this project on your own responsibility. Endomondo can make changes in this API without any warnings.

This is only basic API wrap that does not have any prepared endpoints. Only thing that is possible with this packpage is to authenticate in Endomondo web API and send requests. You can find prepared endpoints in [fabulator/endomondo-api](https://github.com/fabulator/endomondo-api).

### Example
```php
$endomondo = new \Fabulator\Endomondo\EndomondoAPIBase();

$profile = json_decode(((string) $endomondo->login(ENDOMONDO_LOGIN, ENDOMONDO_PASSWORD)->getBody()), true);

$endomondo->setUserId($profile['id']);

echo (string) $endomondo->get('/rest/v1/users/5635433/sports')->getBody();
```