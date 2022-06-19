BEGIN;

-- Create a table for calendar events, with possible parent/child relation
CREATE TABLE public.events (
   eventid SERIAL PRIMARY KEY,
   title TEXT,
   description_html TEXT,
   start_time TIMESTAMPTZ,
   end_time TIMESTAMPTZ,
   hostid INTEGER REFERENCES member (memberid),
   rrule TEXT DEFAULT '',
   master_id INTEGER REFERENCES events (eventid) NULL DEFAULT NULL
);

COMMIT;
