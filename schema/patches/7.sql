BEGIN;

ALTER TABLE sis2.member_signin
    ADD COLUMN location INTEGER REFERENCES schedule.location (location_id);

CREATE TABLE sis2.guest_signin (
    guest_signin_id SERIAL PRIMARY KEY,
    signerid INTEGER REFERENCES public.member (memberid),
    show_season_timeslot_id INTEGER REFERENCES schedule.show_season_timeslot (show_season_timeslot_id),
    location INTEGER REFERENCES schedule.location (location_id),
    sign_time TIMESTAMP DEFAULT now(), -- would like to use TIMESTAMPTZ but member_signin doesn't, consistency :(
    guest_info TEXT
);

COMMIT;
