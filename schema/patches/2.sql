BEGIN;

CREATE TABLE myradio.analytics (
    analytics_record_id BIGSERIAL PRIMARY KEY,
    page TEXT,
    ref TEXT DEFAULT NULL,
    member_officerships integer[],
    member_shows_bucketed integer,
    session_id VARCHAR(32)
);

CREATE FUNCTION myradio.create_analytics_record(
    page TEXT,
    ref TEXT,
    memberid INTEGER,
    session_id VARCHAR(32)
)
RETURNS VOID
AS $$
    DECLARE
        officerships INTEGER[];
        num_shows INTEGER;
    BEGIN
        -- Find officerships
        SELECT array_agg(officerid) INTO officerships
        FROM public.member_officer
        WHERE member_officer.memberid = create_analytics_record.memberid
        AND from_date <= NOW()
        AND (till_date IS NULL OR till_date >= (NOW() + '28 days'::INTERVAL));

        -- Find number of shows
        SELECT COUNT(*) INTO num_shows
        FROM schedule.show
        WHERE show.memberid=create_analytics_record.memberid
        OR show_id IN (
               SELECT show_id FROM schedule.show_credit
                  WHERE creditid=myr_create_analytics_record.memberid AND
                      (effective_to >= NOW() OR effective_to IS NULL)
        );

        -- Right, we've got what we need
        INSERT INTO myradio.analytics
        (page, ref, member_officerships, member_shows_bucketed, session_id)
        VALUES (
                myr_create_analytics_record.page,
                myr_create_analytics_record.ref,
                officerships,
                CEIL(num_shows::decimal / 5),
                myr_create_analytics_record.session_id
               );
    END;
    $$
LANGUAGE plpgsql;

COMMIT;