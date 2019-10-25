SET search_path = schedule, pg_catalog;
CREATE TABLE show_subtypes
(
    show_subtype_id INTEGER NOT NULL,
    name            text    NOT NULL,
    colour          CHAR(6) NOT NULL
);

COMMENT ON TABLE show_subtypes IS 'The various subtypes of show (music, news etc.)';
COMMENT ON COLUMN show_subtypes.name IS 'The publicly visible name of the subtype.';
COMMENT ON COLUMN show_subtypes.colour IS 'The colour of the subtype, as a hex colour code without the leading # (eg. E91E61)';

INSERT INTO show_subtypes (show_subtype_id, name, colour)
VALUES (1,
        'Regular',
        '002d5a'),
       (2,
        'Primetime',
        'feb93a'),
       (3,
        'Events',
        'd0011b'),
       (4,
        'News',
        '5A162E'),
       (4,
        'Speech',
        '2A722B'),
       (5,
        'Music',
        '562179'),
       (6,
        'Collaboration',
        'd14fda');

-- we're not using a show_subtype join table, because it
-- makes no sense for a show to have multiple subtypes
ALTER TABLE show ADD COLUMN subtype INTEGER NOT NULL DEFAULT 1;
ALTER TABLE show ADD CONSTRAINT show_subtype_fkey FOREIGN KEY (subtype) REFERENCES schedule.show_subtypes(show_subtype_id) ON DELETE SET DEFAULT;
COMMENT ON COLUMN show.subtype IS 'The subtype of the show - determines its colour on the website.';
