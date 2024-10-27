# Run DRUMKIT with Docker
The easiest way is probably to run it in docker:

```
docker run \
    -e DRUMKIT_TLS_KEY=/ssl/mercure-router.local-key.pem \
    -e DRUMKIT_TLS_CERT=/ssl/mercure-router.local.pem \
    -e DRUMKIT_CORSORIGIN=mercure-router.local \
    -e DRUMKIT_SECURITY_PUBLISHER_KEY='!ChangeThisMercureHubJWTSecretKey!' \
    -e DRUMKIT_SECURITY_SUBSCRIBER_KEY='!ChangeThisMercureHubJWTSecretKey!' \
    -v ./ssl:/ssl \
    --rm -it nekdev/drumkit
```

Please notice that you must provide SSL certificates to run drumkit.

You may want to use mkcert to quickly generate certificates in local:

```bash
# Generate a certification and install it in your browsers
mkcert -install

# Create a certificate for mercure-router.local
# Change the value of the option corsOrigin to make it work with drumkit
mkcert -cert-file ssl/mercure-router.local.pem -key-file ssl/mercure-router.local-key.pem "mercure-router.local"
