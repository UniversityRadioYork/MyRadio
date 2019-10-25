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

CREATE SEQUENCE show_subtype_id_seq
    START WITH 7
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE show_subtype_id_seq OWNED BY show_subtypes.show_subtype_id;
ALTER TABLE ONLY show_subtypes ALTER COLUMN show_subtype_id SET DEFAULT nextval('show_subtype_id_seq');

CREATE TABLE show_season_subtype (
    show_season_subtype_id INTEGER NOT NULL,
    show_id INTEGER DEFAULT NULL,
    season_id INTEGER DEFAULT NULL,
    show_subtype_id INTEGER NOT NULL DEFAULT 1,
    effective_from TIMESTAMP WITH TIME ZONE,
    effective_to TIMESTAMP WITH TIME ZONE
);
ALTER TABLE show_season_subtype ADD CONSTRAINT chk_subtype_show_or_season_id CHECK (show_id IS NOT NULL OR season_id IS NOT NULL);
ALTER TABLE show_season_subtype ADD CONSTRAINT fk_show_subtype FOREIGN KEY (show_subtype_id) REFERENCES show_subtypes (show_subtype_id) ON DELETE SET DEFAULT;
ALTER TABLE show_season_subtype ADD CONSTRAINT fk_subtype_show FOREIGN KEY (show_id) REFERENCES show (show_id) ON DELETE CASCADE;
ALTER TABLE show_season_subtype ADD CONSTRAINT fk_subtype_season FOREIGN KEY (season_id) REFERENCES show_season (show_season_id) ON DELETE CASCADE;

CREATE SEQUENCE show_season_subtype_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE show_season_subtype_id_seq OWNED BY show_season_subtype.show_season_subtype_id;
ALTER TABLE ONLY show_season_subtype ALTER COLUMN show_season_subtype_id SET DEFAULT nextval('show_season_subtype_id_seq');