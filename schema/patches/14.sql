BEGIN;
INSERT INTO metadata_key (
        name,
        allow_multiple,
        description,
        cache_duration,
        plural,
        searchable
    )
VALUES (
        'autoviz_enabled',
        false,
        'Whether to automatically visualise this show',
        60,
        false,
        false
    );
COMMIT;