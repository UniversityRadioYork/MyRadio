BEGIN;

CREATE TABLE myradio.analytics (
    analytics_record_id BIGSERIAL PRIMARY KEY,
    page TEXT,
    referrer TEXT,
    memberid INTEGER REFERENCES public.member (memberid) NULL,
    session_id VARCHAR(32)
);

ALTER TABLE public.member
    ADD COLUMN analytics_consent CHAR(1) DEFAULT 'n';

CREATE FUNCTION myr_create_analytics_record(
    page TEXT,
    referrer TEXT,
    memberid INTEGER,
    session_id VARCHAR(32)
)
AS $$
    BEGIN
        IF (SELECT analytics_consent FROM public.member WHERE member.memberid = memberid)
    END;
    $$
LANGUAGE plpgsql;

COMMIT;