
Prepare dev environment
-----------------------

```
echo "127.0.0.1	mercure-router.local" | sudo tee --append /etc/host > /dev/null
make setup
```

Running in production
---------------------

Read this: https://amphp.org/http-server/production


Note about the original mercure
-------------------------------

Running it:

```
MERCURE_PUBLISHER_JWT_KEY='!ChangeMe!' \ 
MERCURE_SUBSCRIBER_JWT_KEY='!ChangeMe!' \
./mercure run -config Caddyfile.dev
```


```
docker run \
    -e MERCURE_PUBLISHER_JWT_KEY='!ChangeMe!' \
    -e MERCURE_SUBSCRIBER_JWT_KEY='!ChangeMe!' \
    -p 80:80 \
    -p 443:443 \
    dunglas/mercure caddy run -config /etc/caddy/Caddyfile.dev
```

