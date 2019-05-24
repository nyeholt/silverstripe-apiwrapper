# Advanced Usage

## Information

## Configuration

Show all configuration values in YML format with their default value

ie.
```yml
SilbinaryWolf\SteamedClams\ClamAV:
  # Make this the same as your clamd.conf settings
  clamd:
    LocalSocket: '/var/run/clamav/clamd.ctl'
  # If true and the ClamAV daemon isn't running or isn't installed the file will be denied as if it has a virus.
  deny_on_failure: false
  # For configuring on existing site builds and ignoring the scanning of pre-module install `File` records. 
  initial_scan_ignore_before_datetime: '1970-12-25 00:00:00'
```
