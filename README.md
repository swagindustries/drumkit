Prepare dev environment
-----------------------

```
echo "127.0.0.1	mercure-router.local" | sudo tee --append /etc/host > /dev/null
make setup
```


Note about the original mercure
-------------------------------

Running it:

```
MERCURE_PUBLISHER_JWT_KEY='!ChangeMe!' \ 
MERCURE_SUBSCRIBER_JWT_KEY='!ChangeMe!' \
./mercure run -config Caddyfile.dev
```
