# Test process

## Vagrant setup

As usual,
```
vagrant up
vagrant ssh
```

(And navigate to the /vagrant dir )

## Running tests

Setup to install the dependencies.
Requires bootstrap.sh to have already been run first (Vagrant handles this when
provisioning), and also requires elevated permissions to run

```
sudo ./scripts/install-tests.sh
```

These scripts use the database "myradio_test", so be sure to update
MyRadio_Config.local.php to point to this database. This script should never be
run in a production environment, due to the permissions it gives the `myradio`
database user.

At this point, execute `./scripts/run-tests.sh` to run the tests. This script
will reset the database state each time.

