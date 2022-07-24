Drumkit
=======

Mercure hub, in PHP.

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

Backward compatibility promise
------------------------------

This project is NOT designed to be used as a library. It provides a single node mercure server.

This is why **no backward compatibility is provided** on any class ATM.

But you can expect no behavior change in minor version, including:
- Configuration files format
- Command options

This project follows [semver](https://semver.org/) and so we may break any of the previous statement on major version
learn more in the CHANGELOG.md file provided as well.

TODO
----

- Switch to amphp router
- Use new amphp version (with fibers)
- Complete support for authentication
