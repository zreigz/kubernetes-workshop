mysql-init Dockerfile
=====================

This image runs a set of user-provided scripts to initialize a MySQL database.
These are standard `.sql` scripts that can manage users and databases as well as
import schemas.


Configuration
-------------

| Variable              | Default          | Description                     |
|-----------------------|------------------|---------------------------------|
| `MYSQL_INIT_HOST`     | `mysql`          | MySQL server host               |
| `MYSQL_INIT_PORT`     | `3306`           | MySQL server port               |
| `MYSQL_INIT_USERNAME` | `root`           | MySQL user w/ needed privileges |
| `MYSQL_INIT_PASSWORD` | `secretmysql`    | Password for given user         |
| `MYSQL_INIT_RANDOM_PASSWORD`     | unset | If `true`, reset the user password    |
| `MYSQL_INIT_DISABLE_REMOTE_ROOT` | unset | If `true`, disable remote root login  |
| `MYSQL_INIT_SET_PASSWORD`        | unset | If set, reset password to given value |
| `MYSQL_INIT_WAIT_RETRIES`        | `24` | Number of connection attempts to make  |
| `MYSQL_INIT_WAIT_DELAY`          | `5`  | Seconds to wait between retry attempts |


While this image requires access (probably `root`-level) to a MySQL instance at
startup, `MYSQL_INIT_DISABLE_REMOTE_LOGIN`, `MYSQL_INIT_RANDOM_PASSWORD`, and
`MYSQL_INIT_CHANGE_PASSWORD` may be used to better lock down the container
once initialization has completed successfully. These commands will only run
if all scripts could be executed successfully.

Notes about password-related variables:
 * `MYSQL_INIT_DISABLE_REMOTE_LOGIN` will remove access from all wildcard hosts
   to the `root` account. As such, it requires privileges to modify the
   `mysql.users` table.
 * `MYSQL_INIT_RANDOM_PASSWORD` will set the specified user's password to a
   random value by running `pwgen -1 32`. It will be written as a line to stdout
   of the form `GENERATED ${MYSQL_INIT_USERNAME} PASSWORD: $(pwgen -1 32)`

User scripts
------------

SQL scripts placed in `/mysql-init.d/*.sql` will be executed on startup using
the configured account. Note that no database will be initially selected, so
be sure to include `USE ...` statements as needed.

Scripts are executed in alphabetical order as dictated by shell globbing. To
ensure scripts run in a particular order, a naming convention like
`xx-scriptname.sql` is recommended, where `xx` is two numbers indicating the
priority. These will be executed from least (`00`) to greatest (`99`).

The container has Monasca-specific scripts included. To use your own, you can
mount over the directory with a Docker volume mount:

    docker run --rm=true -v /path/to/new/scripts:/mysql-init.d zreigz/mysql-init:latest

Templating
----------

SQL scripts may include [Jinja2 templates][6] to, e.g., apply environment
configurations like passwords at runtime. To make use of this, append a `.j2` to
your `.sql` script, e.g. `/mysql-init.d/01-myscript.sql.j2`. Templates will be
applied at startup before any scripts have been applied to the database.

Templates have access to all environment variables, such as
`{{ MYSQL_INIT_HOST }}`, as well as all built-in Jinja2 filters.
