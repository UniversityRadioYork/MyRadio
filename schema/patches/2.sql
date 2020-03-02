BEGIN;

DROP SCHEMA IF EXISTS bookings CASCADE;
CREATE SCHEMA bookings;
SET search_path = bookings;

-- e.g. "studio", "recorder" etc.
CREATE TABLE resource_classes (
    resource_class_id SERIAL PRIMARY KEY,
    name TEXT
);

CREATE TABLE resources (
    resource_id SERIAL PRIMARY KEY,
    name TEXT,
    resource_class_id INTEGER REFERENCES resource_classes(resource_class_id)
);

CREATE TABLE bookings (
    booking_id SERIAL PRIMARY KEY,
    start_time TIMESTAMP WITH TIME ZONE,
    end_time TIMESTAMP WITH TIME ZONE,
    priority SMALLINT,
    creator INTEGER REFERENCES public.member(memberid)
);

CREATE TABLE booking_resources (
    booking_resource_id SERIAL PRIMARY KEY,
    booking_id INTEGER REFERENCES bookings(booking_id),
    resource_id INTEGER REFERENCES resources(resource_id)
);

CREATE TABLE booking_members (
    booking_user_id SERIAL PRIMARY KEY,
    booking_id INTEGER REFERENCES bookings(booking_id),
    member_id INTEGER REFERENCES public.member(memberid)
);

COMMIT;