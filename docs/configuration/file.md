# Configure DRUMKIT with a file

File configuration is the most complete way to configure DRUMKIT. You can find an extensive config example
[here](../../tests/fixtures/full_configuration.json).

To run DRUMKIT with a config file you should use the following configuration:

```bash
drumkit --config=/path/to/your/configuration
```

_DRUMKIT uses [JSON5](https://json5.org/) as configuration format, this means you can comment the JSON config if you want._
