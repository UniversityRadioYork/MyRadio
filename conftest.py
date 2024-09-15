from pathlib import Path
from typing import Dict
import pytest
import subprocess
import psycopg2
import hashlib
import json
from playwright.sync_api import Playwright, Browser  # type: ignore


def _get_myradio_config(field: str):
    return json.loads(
        subprocess.check_output(
            [
                "php",
                "-r",
                f'require "src/Controllers/root_cli.php"; echo json_encode(\\MyRadio\\Config::${field});',
            ]
        ).decode("utf-8")
    )


@pytest.fixture(autouse=True, scope="session")
def db_conn():
    # Get the config details
    host = _get_myradio_config("db_hostname")
    user = _get_myradio_config("db_user")
    password = _get_myradio_config("db_pass")

    db = psycopg2.connect(
        f"host={host} dbname=postgres user={user} password={password}"
    )
    # Create myradio database and seed
    db.autocommit = True
    cursor = db.cursor()
    cursor.execute("DROP DATABASE IF EXISTS myradio_test")
    cursor.execute("CREATE DATABASE myradio_test")
    cursor.execute("DROP USER IF EXISTS myradio_test")
    cursor.execute("CREATE USER myradio_test WITH PASSWORD 'myradio_test'")
    cursor.execute("GRANT ALL PRIVILEGES ON DATABASE myradio_test TO myradio_test")
    cursor.close()
    db.close()
    db = psycopg2.connect(
        f"host={host} dbname=myradio_test user={user} password={password}"
    )
    db.autocommit = True

    schema_dir = Path(__file__).parent / "schema"
    sample_configs_dir = Path(__file__).parent / "sample_configs"
    for file in [
        schema_dir / "base.sql",
        *sorted(filter(lambda f: f.suffix == '.sql', (schema_dir / "patches").iterdir()), key=lambda patch: int(patch.stem)),
        sample_configs_dir / "test-auth.sql",
    ]:
        with file.open() as f:
            cur = db.cursor()
            cur.execute("SET search_path = 'public'")
            cur.execute(f.read())
            db.commit()
            cur.close()

    yield db

    db.close()
    db = psycopg2.connect(
        f"host={host} dbname=postgres user={user} password={password}"
    )
    db.autocommit = True
    cursor = db.cursor()
    cursor.execute("DROP DATABASE myradio_test")
    cursor.execute("DROP USER myradio_test")
    cursor.close()
    db.close()


def _make_pool_db(conn: psycopg2.extensions.connection, name: str):
    cur = conn.cursor()
    cur.execute(f"DROP DATABASE IF EXISTS {name}")
    cur.execute(f"CREATE DATABASE {name} TEMPLATE myradio_test")
    cur.close()
    return name


def _release_pool_db(conn: psycopg2.extensions.connection, name: str):
    cur = conn.cursor()
    cur.execute(f"DROP DATABASE {name}")
    cur.close()


phase_report_key = pytest.StashKey[Dict[str, pytest.CollectReport]]()
@pytest.hookimpl(wrapper=True, tryfirst=True)
def pytest_runtest_makereport(item, call):
    # execute all other hooks to obtain the report object
    rep = yield

    # store test results for each phase of a call, which can
    # be "setup", "call", "teardown"
    item.stash.setdefault(phase_report_key, {})[rep.when] = rep

    return rep


@pytest.fixture(autouse=True, scope="function")
def myradio_database(request: pytest.FixtureRequest, db_conn):
    name = request.node.name
    name_hash = hashlib.sha256(name.encode()).hexdigest()[:8]
    db_name = f"myradio_test_{name_hash}"
    _make_pool_db(db_conn, db_name)
    yield db_name
    report = request.node.stash[phase_report_key]
    if "call" not in report or report["call"].failed:
        print(f"Test {name} failed, keeping database {db_name}")
    else:
        _release_pool_db(db_conn, db_name)


INSIDE_MYRADIO_CONTAINER = Path("/etc/apache2/sites-available/myradio.conf").is_file()


@pytest.fixture()
def api_v2(myradio_database, playwright: Playwright):
    ctx = playwright.request.new_context(
        base_url=f"http://localhost:{80 if INSIDE_MYRADIO_CONTAINER else 7080}/api/v2/",
        extra_http_headers={
            "X-MyRadio-Database": myradio_database,
            "APIKey": "test-key",  # schema/test-auth.sql
        },
    )
    yield ctx
    ctx.dispose()


@pytest.fixture()
def browser(myradio_database, browser: Browser):
    ctx = browser.new_context(
        base_url=f"http://localhost:{80 if INSIDE_MYRADIO_CONTAINER else 7080}/myradio",
        extra_http_headers={"X-MyRadio-Database": myradio_database},
    )
    yield ctx
    ctx.close()
