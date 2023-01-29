BEGIN;

CREATE TABLE myradio.audit_log (
    log_entry_id BIGSERIAL PRIMARY KEY,
    entry_type TEXT,
    target_class TEXT,
    target_id BIGINT,
    actor_id INTEGER REFERENCES public.member (memberid),
    payload JSONB,
    entry_time TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_audit_types ON myradio.audit_log (entry_type);
CREATE INDEX idx_audit_class_id ON myradio.audit_log (target_class, target_id);
CREATE INDEX idx_audit_actor ON myradio.audit_log (actor_id);

UPDATE myradio.schema
    SET value = 16
    WHERE attr='version';

COMMIT;
