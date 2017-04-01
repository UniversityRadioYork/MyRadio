CREATE SCHEMA bapsplanner;
COMMENT ON SCHEMA bapsplanner IS 'Used by Show Planner.';
CREATE SCHEMA jukebox;
COMMENT ON SCHEMA jukebox IS 'Used by jukebox auto-music-player.';
CREATE SCHEMA mail;
COMMENT ON SCHEMA mail IS 'Mailing lists.';
CREATE SCHEMA metadata;
COMMENT ON SCHEMA metadata IS 'Metadata systems.';
CREATE SCHEMA music;
COMMENT ON SCHEMA music IS 'Charts and possibly other stuff too.';
CREATE SCHEMA myury;
COMMENT ON SCHEMA myury IS 'Schema for new/migrated data for the Members Internal replacement';
CREATE SCHEMA people;
COMMENT ON SCHEMA people IS 'Tables for the LeRouge Extensible Roles (Or User Groups) Engine, as well as the quotes board.';
CREATE SCHEMA schedule;
COMMENT ON SCHEMA schedule IS 'Schema for the MyRadio schedule.';
CREATE SCHEMA sis2;
COMMENT ON SCHEMA sis2 IS 'Used by Studio Infomation Service.';
CREATE SCHEMA tracklist;
COMMENT ON SCHEMA tracklist IS 'Provides a schema that logs played out tracks for PPL track returns';
CREATE SCHEMA uryplayer;
COMMENT ON SCHEMA uryplayer IS 'URY Player';
CREATE SCHEMA webcam;
CREATE SCHEMA website;
COMMENT ON SCHEMA website IS 'Collection of data relating to the operation of the public-facing website.';
CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;
CREATE FUNCTION bapstotracklist() RETURNS TRIGGER AS $$
DECLARE
    audid INTEGER;
BEGIN
    IF ((TG_OP = 'UPDATE')
        AND ((SELECT COUNT(*) FROM (SELECT sel.action FROM selector sel
                                    WHERE sel.action >= 4 AND sel.action <= 11
                                    ORDER BY sel.TIME DESC LIMIT 1) AS seltop
              INNER JOIN tracklist.selbaps bsel ON (seltop.action = bsel.selaction)
              WHERE bsel.bapsloc = NEW."serverid" AND (NEW."timeplayed" >= (SELECT sel.TIME FROM selector sel
                                                                            ORDER BY sel.TIME DESC
                                                                            LIMIT 1))) = 1)
        AND ((SELECT COUNT(*) FROM baps_audio ba WHERE ba.audioid = NEW."audioid" AND ba.trackid > 0) = 1)
        AND ((NEW."timestopped" - NEW."timeplayed" > '00:00:30'))
        AND ((SELECT COUNT(*) FROM tracklist.tracklist WHERE bapsaudioid = NEW."audiologid") = 0))
    THEN
        INSERT INTO tracklist.tracklist (SOURCE, timestart, timestop, timeslotid, bapsaudioid)
            VALUES ('b', NEW."timeplayed", NEW."timestopped", (SELECT show_season_timeslot_id FROM schedule.show_season_timeslot
                                                               WHERE start_time <= NOW()
                                                                   AND (start_time + duration) >= NOW()
                                                                   AND show_season_id != 0
                                                               ORDER BY show_season_timeslot_id ASC
                                                               LIMIT 1),
                    NEW."audiologid")
            RETURNING audiologid INTO audid;
        INSERT INTO tracklist.track_rec
            VALUES ("audid", (SELECT rec.recordid FROM rec_track rec
                              INNER JOIN baps_audio ba USING (trackid)
                              WHERE ba.audioid = NEW."audioid"),
                    (SELECT trackid FROM baps_audio WHERE audioid = NEW."audioid"));
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE FUNCTION clear_item_func() RETURNS trigger AS $$
BEGIN
    IF OLD.textitemid IS NOT NULL
    THEN
        DELETE FROM baps_textitem WHERE textitemid = OLD.textitemid;
    END IF;
    IF OLD.libraryitemid IS NOT NULL
    THEN
        DELETE FROM baps_libraryitem WHERE libraryitemid = OLD.libraryitemid;
    END IF;
    IF OLD.fileitemid IS NOT NULL
    THEN
        DELETE FROM baps_fileitem WHERE fileitemid = OLD.fileitemid;
    END IF;
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE FUNCTION process_gammu_text() RETURNS trigger AS $$
BEGIN
    IF (TG_OP = 'INSERT')
    THEN
        IF ((SELECT show_season_timeslot_id FROM schedule.show_season_timeslot
             WHERE start_time <= NOW() AND (start_time + duration) >= NOW()
             ORDER BY show_season_timeslot_id ASC
             LIMIT 1) IS NOT NULL)
        THEN
            INSERT INTO sis2.messages (commtypeid, timeslotid, sender, subject, content, statusid)
                VALUES (2, (SELECT show_season_timeslot_id FROM schedule.show_season_timeslot
                            WHERE start_time <= NOW() AND (start_time + duration) >= NOW()
                            ORDER BY show_season_timeslot_id ASC
                            LIMIT 1),
                        NEW."SenderNumber", NEW."TextDecoded", NEW."TextDecoded", 1);
            RETURN NEW;
        ELSE
            INSERT INTO sis2.messages (commtypeid, timeslotid, sender, subject, content, statusid)
                VALUES (2, 118540, NEW."SenderNumber", NEW."TextDecoded", NEW."TextDecoded", 1);
            RETURN NEW;
        END IF;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE FUNCTION set_shelfcode_func() RETURNS trigger AS $$
DECLARE
    myshelfnumber integer DEFAULT 0;
    recordrow RECORD;
BEGIN
    IF ((NEW.media = '7' OR NEW.media = '2') AND NEW.format = 'a')
    THEN
        FOR recordrow IN (SELECT * FROM rec_record
                          WHERE media = NEW.media AND (format = '7' OR format = '2') AND shelfletter = NEW.shelfletter
                          ORDER BY shelfnumber)
        LOOP
            IF (recordrow.shelfnumber > myshelfnumber + 1)
            THEN
                EXIT;
            END IF;
            myshelfnumber = myshelfnumber + 1;
        END LOOP;
    ELSE
        FOR recordrow IN (SELECT * FROM rec_record
                          WHERE media = NEW.media AND format = NEW.format AND shelfletter = NEW.shelfletter
                          ORDER BY shelfnumber)
        LOOP
            IF (recordrow.shelfnumber > myshelfnumber + 1)
            THEN
                EXIT;
            END IF;
            myshelfnumber = myshelfnumber + 1;
        END LOOP;
    END IF;
    NEW.shelfnumber = myshelfnumber + 1;
RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE FUNCTION update_timestamp() RETURNS trigger AS $$
BEGIN
    NEW."UpdatedInDB" := LOCALTIMESTAMP(0);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE SEQUENCE bapsplanner.auto_playlists_autoplaylistid_seq
    START WITH 3
    INCREMENT BY 1
    MINVALUE 3
    NO MAXVALUE
    CACHE 1;
CREATE TABLE bapsplanner.auto_playlists (
    auto_playlist_id integer DEFAULT nextval('bapsplanner.auto_playlists_autoplaylistid_seq'::regclass) NOT NULL,
    name character varying(30) NOT NULL,
    query text
);
CREATE SEQUENCE bapsplanner.client_ids_client_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
CREATE TABLE bapsplanner.client_ids (
    client_id integer DEFAULT nextval('bapsplanner.client_ids_client_id_seq'::regclass) NOT NULL,
    show_season_timeslot_id integer,
    session_id character varying(64)
);
CREATE SEQUENCE bapsplanner.managed_items_manageditemid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
CREATE TABLE bapsplanner.managed_items (
    manageditemid integer DEFAULT nextval('bapsplanner.managed_items_manageditemid_seq'::regclass) NOT NULL,
    managedplaylistid integer NOT NULL,
    title character varying NOT NULL,
    length time without time zone,
    bpm smallint,
    expirydate date,
    memberid integer
);
CREATE SEQUENCE bapsplanner.managed_items_managedplaylistid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE bapsplanner.managed_items_managedplaylistid_seq OWNED BY bapsplanner.managed_items.managedplaylistid;
CREATE TABLE bapsplanner.managed_playlists (
    managedplaylistid integer NOT NULL,
    name character varying,
    folder character varying,
    item_ttl integer
);
COMMENT ON COLUMN bapsplanner.managed_playlists.item_ttl IS 'The default period of time an item in this playlist will live for, in seconds. A value of NULL will not expire.';
CREATE SEQUENCE bapsplanner.managed_playlists_managedplaylistid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE bapsplanner.managed_playlists_managedplaylistid_seq OWNED BY bapsplanner.managed_playlists.managedplaylistid;
CREATE TABLE bapsplanner.managed_user_items (
    manageditemid integer DEFAULT nextval('bapsplanner.managed_items_manageditemid_seq'::regclass) NOT NULL,
    managedplaylistid character varying(35) NOT NULL,
    title character varying NOT NULL,
    length time without time zone,
    bpm smallint
);
CREATE TABLE bapsplanner.secure_play_token (
    sessionid character varying(32) NOT NULL,
    memberid integer NOT NULL,
    "timestamp" timestamp without time zone DEFAULT now() NOT NULL,
    trackid integer NOT NULL
);
COMMENT ON TABLE bapsplanner.secure_play_token IS 'Stores ''tokens'' that allow a user to play a file - once the file is played the token is removed preventing downloads or sharing.';
CREATE TABLE bapsplanner.timeslot_change_ops (
    timeslot_change_set_id integer NOT NULL,
    client_id integer,
    change_ops text,
    "timestamp" timestamp without time zone DEFAULT now()
);
COMMENT ON TABLE bapsplanner.timeslot_change_ops IS 'Stores changes to a show plan in the Planner''s standard JSON Operation Notation (JSONON)';
CREATE SEQUENCE bapsplanner.timeslot_change_ops_timeslot_change_set_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE bapsplanner.timeslot_change_ops_timeslot_change_set_id_seq OWNED BY bapsplanner.timeslot_change_ops.timeslot_change_set_id;
CREATE TABLE bapsplanner.timeslot_items (
    timeslot_item_id integer NOT NULL,
    timeslot_id integer NOT NULL,
    channel_id smallint NOT NULL,
    weight integer NOT NULL,
    rec_track_id integer,
    managed_item_id integer,
    user_item_id integer,
    legacy_aux_id integer
);
CREATE SEQUENCE bapsplanner.timeslot_items_timeslot_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE bapsplanner.timeslot_items_timeslot_item_id_seq OWNED BY bapsplanner.timeslot_items.timeslot_item_id;
SET search_path = jukebox, pg_catalog;
CREATE SEQUENCE playlist_availability_playlist_availability_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
CREATE TABLE playlist_availability (
    memberid integer,
    approvedid integer,
    effective_from timestamp with time zone NOT NULL,
    effective_to timestamp with time zone,
    playlist_availability_id integer DEFAULT nextval('jukebox.playlist_availability_playlist_availability_id_seq'::regclass) NOT NULL,
    weight integer NOT NULL,
    playlistid character varying NOT NULL
);
ALTER SEQUENCE playlist_availability_playlist_availability_id_seq OWNED BY playlist_availability.playlist_availability_id;
CREATE TABLE playlist_entries (
    playlistid character varying(15) NOT NULL,
    trackid integer NOT NULL,
    revision_added integer NOT NULL,
    revision_removed integer,
    entryid integer NOT NULL
);
CREATE SEQUENCE playlist_entries_entryid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE playlist_entries_entryid_seq OWNED BY playlist_entries.entryid;
CREATE TABLE playlist_revisions (
    playlistid character varying(15) NOT NULL,
    revisionid integer NOT NULL,
    "timestamp" timestamp without time zone DEFAULT now() NOT NULL,
    author integer NOT NULL,
    notes text
);
CREATE SEQUENCE playlist_timeslot_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
CREATE TABLE playlist_timeslot (
    id integer DEFAULT nextval('playlist_timeslot_id_seq'::regclass) NOT NULL,
    memberid integer NOT NULL,
    approvedid integer,
    day smallint NOT NULL,
    start_time time without time zone NOT NULL,
    end_time time without time zone NOT NULL,
    playlist_availability_id integer NOT NULL
);
COMMENT ON COLUMN playlist_timeslot.day IS '1-7 (1=Monday)';
CREATE TABLE playlists (
    playlistid character varying(15) NOT NULL,
    title character varying(50) NOT NULL,
    image character varying(50) DEFAULT 'music_note.png'::character varying,
    description character varying(250),
    lock integer,
    locktime integer,
    weight integer DEFAULT 0 NOT NULL,
    exported boolean DEFAULT true NOT NULL,
    weightx integer DEFAULT 0 NOT NULL
);
COMMENT ON COLUMN playlists.weight IS 'Relative to the other playlists, how often should a track from this be played on the jukebox? If 0, never play it.';
COMMENT ON COLUMN playlists.weightx IS 'Weightings for post-watershed jukebox.';
CREATE TABLE request (
    request_id integer NOT NULL,
    memberid integer NOT NULL,
    date timestamp with time zone NOT NULL,
    queue character varying NOT NULL,
    trackid integer NOT NULL
);
CREATE SEQUENCE request_request_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE request_request_id_seq OWNED BY request.request_id;
CREATE TABLE silence_log (
    silenceid integer NOT NULL,
    starttime timestamp without time zone DEFAULT now() NOT NULL,
    stoptime timestamp without time zone,
    handledby integer
);
COMMENT ON TABLE silence_log IS 'Stores a log of silence events';
CREATE VIEW silence AS
    SELECT silence_log.starttime FROM silence_log WHERE (silence_log.stoptime = NULL::timestamp without time zone) ORDER BY silence_log.silenceid LIMIT 1;
COMMENT ON VIEW silence IS 'Gets start time of a current silence event. Should check if time is > 1 second';
CREATE SEQUENCE silence_log_silenceid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE silence_log_silenceid_seq OWNED BY silence_log.silenceid;
CREATE TABLE track_blacklist (
    trackid integer NOT NULL
);
COMMENT ON TABLE track_blacklist IS 'A list of Tracks that should, under no circumstances, be played on the jukebox.';
SET search_path = mail, pg_catalog;
CREATE TABLE alias (
    alias_id integer NOT NULL,
    source character varying NOT NULL
);
CREATE SEQUENCE alias_alias_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE alias_alias_id_seq OWNED BY alias.alias_id;
CREATE TABLE alias_list (
    alias_id integer NOT NULL,
    destination integer NOT NULL
);
CREATE TABLE alias_member (
    alias_id integer NOT NULL,
    destination integer NOT NULL
);
CREATE TABLE alias_officer (
    alias_id integer NOT NULL,
    destination integer NOT NULL
);
CREATE SEQUENCE alias_source_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
CREATE TABLE alias_text (
    alias_id integer NOT NULL,
    destination character varying NOT NULL
);
CREATE TABLE email (
    email_id integer NOT NULL,
    sender integer,
    subject text NOT NULL,
    body text NOT NULL,
    "timestamp" timestamp without time zone DEFAULT now()
);
CREATE TABLE email_recipient_list (
    email_id integer NOT NULL,
    listid integer NOT NULL,
    sent boolean DEFAULT false NOT NULL
);
CREATE TABLE email_recipient_member (
    email_id integer NOT NULL,
    memberid integer NOT NULL,
    sent boolean DEFAULT false NOT NULL
);
CREATE SEQUENCE emails_email_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE emails_email_id_seq OWNED BY email.email_id;
SET search_path = metadata, pg_catalog;
CREATE TABLE metadata_key (
    metadata_key_id integer NOT NULL,
    name character varying NOT NULL,
    allow_multiple boolean DEFAULT false,
    description text DEFAULT ''::text NOT NULL,
    cache_duration integer DEFAULT 300 NOT NULL,
    plural character varying(255),
    searchable boolean DEFAULT false NOT NULL
);
COMMENT ON TABLE metadata_key IS 'Stores possible types of textual metadatum. Used by all three _metadata tables';
COMMENT ON COLUMN metadata_key.metadata_key_id IS 'A unique identifier for each metadata type';
COMMENT ON COLUMN metadata_key.name IS 'A human-readable name for the metadata key';
COMMENT ON COLUMN metadata_key.description IS 'A short description of the semantics/meaning of this key, and where it is applicable.';
CREATE SEQUENCE metadata_key_metadata_key_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE metadata_key_metadata_key_id_seq OWNED BY metadata_key.metadata_key_id;
CREATE TABLE package (
    name character varying(50) NOT NULL,
    description text NOT NULL,
    package_id integer NOT NULL,
    weight integer
);
CREATE TABLE package_image_metadata (
    memberid integer,
    approvedid integer,
    effective_from timestamp with time zone,
    effective_to timestamp with time zone,
    metadata_key_id integer NOT NULL,
    metadata_value character varying(100) NOT NULL,
    element_id integer NOT NULL,
    package_image_metadata_id integer NOT NULL,
    package_id integer
);
CREATE SEQUENCE package_image_metadata_package_image_metadata_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE package_image_metadata_package_image_metadata_id_seq OWNED BY package_image_metadata.package_image_metadata_id;
CREATE SEQUENCE package_package_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE package_package_id_seq OWNED BY package.package_id;
CREATE TABLE package_text_metadata (
    memberid integer,
    approvedid integer,
    effective_from timestamp with time zone,
    effective_to timestamp with time zone,
    metadata_key_id integer NOT NULL,
    metadata_value text NOT NULL,
    element_id integer NOT NULL,
    package_text_metadata_id integer NOT NULL,
    package_id integer
);
CREATE SEQUENCE package_text_metadata_package_text_metadata_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE package_text_metadata_package_text_metadata_id_seq OWNED BY package_text_metadata.package_text_metadata_id;
SET search_path = music, pg_catalog;
CREATE TABLE chart_release (
    submitted timestamp with time zone,
    chart_release_id integer NOT NULL,
    chart_type_id integer NOT NULL
);
CREATE SEQUENCE chart_release_chart_release_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE chart_release_chart_release_id_seq OWNED BY chart_release.chart_release_id;
CREATE TABLE chart_row (
    chart_row_id integer NOT NULL,
    chart_release_id integer NOT NULL,
    "position" smallint NOT NULL,
    track character varying(255),
    artist character varying(255),
    trackid integer,
    CONSTRAINT chart_row_position_check CHECK (("position" >= 0))
);
CREATE SEQUENCE chart_row_chart_row_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE chart_row_chart_row_id_seq OWNED BY chart_row.chart_row_id;
CREATE TABLE chart_type (
    name character varying(50) NOT NULL,
    description text NOT NULL,
    chart_type_id integer NOT NULL
);
CREATE SEQUENCE chart_type_chart_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE chart_type_chart_type_id_seq OWNED BY chart_type.chart_type_id;
SET search_path = myury, pg_catalog;
CREATE TABLE act_permission (
    actpermissionid integer NOT NULL,
    serviceid integer NOT NULL,
    moduleid integer,
    actionid integer,
    typeid integer
);
COMMENT ON TABLE act_permission IS 'Specifies what permissions are required in order to use a feature. This is an *OR* type permission system - any of these matching will grant access.
A NULL in the module or action field matches any module or action.
A NULL permissionid means that no permissions are required to use that Service/Module/Action combination.
NULL permissions on wildcards will be ignored.';
CREATE SEQUENCE act_permission_actpermissionid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE act_permission_actpermissionid_seq OWNED BY act_permission.actpermissionid;
CREATE TABLE actions (
    actionid integer NOT NULL,
    moduleid integer,
    name character varying,
    enabled boolean DEFAULT true NOT NULL,
    custom_uri character varying
);
COMMENT ON TABLE actions IS 'Stores Actions within managed MyRadio Service Modules';
CREATE SEQUENCE actions_actionid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE actions_actionid_seq OWNED BY actions.actionid;
CREATE TABLE api_class_map (
    api_map_id integer NOT NULL,
    class_name character varying NOT NULL,
    api_name character varying NOT NULL
);
COMMENT ON TABLE api_class_map IS 'Maps MyRadio Internal classes to the names exposed to the MyRadio API. For example, MyRadio_Track would map to Track. If a class is not mapped, it is not available at all to the API.';
CREATE SEQUENCE api_class_map_api_map_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE api_class_map_api_map_id_seq OWNED BY api_class_map.api_map_id;

CREATE TABLE api_key (
    key_string character varying NOT NULL,
    description character varying NOT NULL,
    revoked boolean DEFAULT false NOT NULL
);
COMMENT ON TABLE api_key IS 'Access keys for the MyRadiopi';
INSERT INTO api_key (key_string, description) VALUES ('IUrnsb8AMkjqDRdfXvOMe3DqHLW8HJ1RNBPNJq3H1FQpiwQDs7Ufoxmsf5xZE9XEbQErRO97DG4xfyVAO7LuS2dOiVNZYoxkk4fEhDt8wR4sLXbghidtM5rLHcgkzO10', 'Swagger Documentation Key');
CREATE TABLE api_key_auth (
    key_string character varying NOT NULL,
    typeid integer NOT NULL
);
COMMENT ON TABLE api_key_auth IS 'Stores what API capabilities each key has.';
CREATE TABLE api_method_auth (
    api_method_auth_id integer NOT NULL,
    class_name character varying NOT NULL,
    method_name character varying,
    typeid integer
);
COMMENT ON TABLE api_method_auth IS 'Assigns permissions to API calls. If a Class or Object Method does not have a permission here, it is accessible only to Keys with "AUTH_APISUDO".
Other than the above exception, the permissions structure is identical to the standard myury action permission system.';
CREATE SEQUENCE api_method_auth_api_method_auth_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE api_method_auth_api_method_auth_id_seq OWNED BY api_method_auth.api_method_auth_id;

CREATE TABLE award_categories (
    awardid integer NOT NULL,
    name character varying NOT NULL
);
CREATE SEQUENCE award_categories_awardid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE award_categories_awardid_seq OWNED BY award_categories.awardid;
CREATE TABLE award_member (
    awardmemberid integer NOT NULL,
    awardid integer NOT NULL,
    memberid integer NOT NULL,
    awarded timestamp without time zone DEFAULT now() NOT NULL,
    awardedby integer NOT NULL
);
CREATE SEQUENCE award_member_awardmemberid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE award_member_awardmemberid_seq OWNED BY award_member.awardmemberid;
CREATE TABLE modules (
    moduleid integer NOT NULL,
    serviceid integer,
    name character varying,
    enabled boolean DEFAULT true NOT NULL
);
COMMENT ON TABLE modules IS 'Stores Modules within MyRadio managed Services';
CREATE SEQUENCE modules_moduleid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE modules_moduleid_seq OWNED BY modules.moduleid;
CREATE TABLE password_reset_token (
    token character varying NOT NULL,
    expires timestamp without time zone NOT NULL,
    used timestamp without time zone,
    memberid integer NOT NULL
);
COMMENT ON TABLE password_reset_token IS 'Tokens used for sending password reset emails.';
CREATE TABLE photos (
    photoid integer NOT NULL,
    owner integer,
    date_added timestamp without time zone DEFAULT now() NOT NULL,
    format character varying DEFAULT 'png'::character varying NOT NULL
);
COMMENT ON COLUMN photos.format IS 'png, jpeg etc - should be the file extension.';
CREATE SEQUENCE photos_photoid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE photos_photoid_seq OWNED BY photos.photoid;
CREATE TABLE services (
    serviceid integer NOT NULL,
    name character varying,
    enabled boolean DEFAULT true NOT NULL
);
COMMENT ON TABLE services IS 'Lists all Services managed by MyRadio';
CREATE SEQUENCE services_serviceid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE services_serviceid_seq OWNED BY services.serviceid;
CREATE TABLE services_versions (
    serviceversionid integer NOT NULL,
    serviceid integer NOT NULL,
    version character varying NOT NULL,
    path character varying NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    proxy_static boolean DEFAULT false
);
COMMENT ON COLUMN services_versions.proxy_static IS 'If true, Twig will be given a base url that proxies all static resources through a JS script to ensure the right version of the file is served.';
CREATE TABLE services_versions_member (
    memberid integer NOT NULL,
    serviceversionid integer NOT NULL
);
CREATE SEQUENCE services_versions_serviceversionid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE services_versions_serviceversionid_seq OWNED BY services_versions.serviceversionid;

CREATE TABLE api_key_log (
    api_log_id integer NOT NULL,
    key_string character varying NOT NULL,
    "timestamp" timestamp without time zone DEFAULT now() NOT NULL,
    remote_ip inet NOT NULL,
    request_path character varying,
    request_params json
);
COMMENT ON TABLE api_key_log IS 'Stores a record of API Requests by an API Key';
CREATE SEQUENCE api_key_log_api_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE api_key_log_api_log_id_seq OWNED BY api_key_log.api_log_id;
SET search_path = people, pg_catalog;
CREATE TABLE credit_type (
    credit_type_id integer NOT NULL,
    name character varying(255) NOT NULL,
    plural character varying(255) NOT NULL,
    is_in_byline boolean DEFAULT false NOT NULL
);
COMMENT ON TABLE credit_type IS 'Types of credit (associations between URY people and items such as shows or podcasts they have taken a role in creating).';
COMMENT ON COLUMN credit_type.plural IS 'A human-readable plural form of the show credit name, for example "presenters" or "producers".';
COMMENT ON COLUMN credit_type.is_in_byline IS 'If true, people credited with this credit type will appear in "with XYZ and ABC" by-lines for the show.';
CREATE TABLE group_root_role (
    group_root_role_id integer NOT NULL,
    role_id_id integer NOT NULL,
    group_type_id integer NOT NULL,
    group_leader_id integer
);
COMMENT ON COLUMN group_root_role.group_leader_id IS 'An optional reference to a role to be considered the ''leader'' role within the group defined by this root.';
CREATE SEQUENCE group_root_role_group_root_role_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE group_root_role_group_root_role_id_seq OWNED BY group_root_role.group_root_role_id;
CREATE TABLE group_type (
    group_type_id integer NOT NULL,
    name character varying(20) NOT NULL
);
CREATE SEQUENCE group_type_group_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE group_type_group_type_id_seq OWNED BY group_type.group_type_id;
CREATE TABLE metadata (
    roleid integer NOT NULL,
    key text NOT NULL,
    value text NOT NULL
);
COMMENT ON TABLE metadata IS 'Key-value store for textual metadata associated with rules.
As of writing the metadata schema is:
''Collective-Name'' - human readable name for the group of everyone in this role, example ''Station Managers''.  This is used as the team name when the role is a grouproot.
''Individual-Name'' - human readable name for an individual in this role, example ''Station Manager''.
''Acronym'' - self-explanatory, example ''SM''.
''Constitution-Section'' - section of the URY constitution defining the role, example: ''4.2.1''.
''Description'' - short description of the role, example: ''Manages the station''.';
COMMENT ON COLUMN metadata.roleid IS 'The unique ID of the role this metadatum concerns.';
COMMENT ON COLUMN metadata.key IS 'The key of the metadatum; should fit the site schema.';
COMMENT ON COLUMN metadata.value IS 'The value of the metadatum.';
CREATE TABLE quote (
    quote_id integer NOT NULL,
    text text NOT NULL,
    source integer NOT NULL,
    date timestamp with time zone DEFAULT now() NOT NULL
);
CREATE SEQUENCE quote_quote_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE quote_quote_id_seq OWNED BY quote.quote_id;
CREATE TABLE role (
    role_id integer NOT NULL,
    alias character varying(100) NOT NULL,
    visibilitylevel integer DEFAULT 1 NOT NULL,
    isactive boolean DEFAULT true NOT NULL,
    ordering integer DEFAULT 1 NOT NULL
);
COMMENT ON TABLE role IS 'Role definitions for LeRouge';
COMMENT ON COLUMN role.role_id IS 'The unique ID of this role.';
COMMENT ON COLUMN role.alias IS 'The internal alias name of this role. (Human readable name is considered metadata)';
COMMENT ON COLUMN role.visibilitylevel IS 'The visibility level of this role; see roles.visibilities.';
COMMENT ON COLUMN role.isactive IS 'If false, this role should be ignored completely during any role actions.';
COMMENT ON COLUMN role.ordering IS 'Coefficient used to determine how high up in lists this role appears (the lower, the more senior).  For officer positions this should reflect the constitutional pecking order; for teams, it just defines the order of teams in any ordered team lists.';
CREATE TABLE role_inheritance (
    child_id integer NOT NULL,
    parent_id integer NOT NULL,
    role_inheritance_id integer NOT NULL
);
COMMENT ON TABLE role_inheritance IS 'Pairs of roles and their immediate parents, used to create the role inheritance graph.';
COMMENT ON COLUMN role_inheritance.child_id IS 'The unique ID of the child row.';
COMMENT ON COLUMN role_inheritance.parent_id IS 'The unique ID of the parent row.';
COMMENT ON COLUMN role_inheritance.role_inheritance_id IS 'The unique ID of this inheritance binding.';
CREATE SEQUENCE role_inheritance_role_inheritance_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE role_inheritance_role_inheritance_id_seq OWNED BY role_inheritance.role_inheritance_id;
CREATE TABLE role_text_metadata (
    metadata_key_id integer NOT NULL,
    role_id integer NOT NULL,
    metadata_value text,
    effective_from timestamp with time zone,
    memberid integer NOT NULL,
    approvedid integer,
    role_text_metadata_id integer NOT NULL,
    effective_to timestamp with time zone
);
COMMENT ON COLUMN role_text_metadata.role_id IS 'The ID of the role this metadatum concerns.';
COMMENT ON COLUMN role_text_metadata.role_text_metadata_id IS 'The unique numeric ID of this metadatum.';
CREATE SEQUENCE role_metadata_role_metadata_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE role_metadata_role_metadata_id_seq OWNED BY role_text_metadata.role_text_metadata_id;
CREATE TABLE role_visibility (
    role_visibility_id integer NOT NULL,
    name character varying(100) NOT NULL,
    description text
);
COMMENT ON TABLE role_visibility IS 'Enumeration of types of visibility and their human readable names and descriptions; used to ensure at the database level that incorrect visibilities cannot be used.';
COMMENT ON COLUMN role_visibility.role_visibility_id IS 'The unique ID of this visibility level.';
COMMENT ON COLUMN role_visibility.name IS 'A human-readable short name for the visibility level.';
COMMENT ON COLUMN role_visibility.description IS 'Optional description for the visibility level.';
CREATE SEQUENCE roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE roles_id_seq OWNED BY role.role_id;
CREATE SEQUENCE "schedule.showcredittype_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE "schedule.showcredittype_id_seq" OWNED BY credit_type.credit_type_id;
CREATE SEQUENCE types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE types_id_seq OWNED BY role_visibility.role_visibility_id;
SET search_path = public, pg_catalog;
SET default_with_oids = true;
CREATE TABLE auth (
    memberid integer NOT NULL,
    lookupid integer NOT NULL,
    starttime timestamp with time zone DEFAULT now() NOT NULL,
    endtime timestamp with time zone,
    CONSTRAINT auth_check CHECK (((endtime IS NULL) OR (starttime < endtime)))
);
COMMENT ON TABLE auth IS 'Grants users temporary permissions on the back end.';
COMMENT ON COLUMN auth.lookupid IS 'Permission granded to the user';
COMMENT ON COLUMN auth.endtime IS 'Permission runs out now; NULL = permenant.';
SET default_with_oids = false;
CREATE TABLE auth_group (
    id integer NOT NULL,
    name character varying(80) NOT NULL
);
CREATE SEQUENCE auth_group_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE auth_group_id_seq OWNED BY auth_group.id;
SET default_with_oids = true;
CREATE TABLE auth_officer (
    officerid integer NOT NULL,
    lookupid integer NOT NULL
);
COMMENT ON TABLE auth_officer IS 'Grants permanent back end permissions to people currently holding officer posts.';
SET default_with_oids = false;
CREATE TABLE auth_subnet (
    typeid integer NOT NULL,
    subnet cidr NOT NULL
);
COMMENT ON TABLE auth_subnet IS 'Allow users logged in from specific clients machines access to additional resources';
CREATE TABLE auth_trainingstatus (
    typeid integer NOT NULL,
    presenterstatusid integer NOT NULL
);
COMMENT ON TABLE auth_trainingstatus IS 'Permissions granted to users with the given training status';
CREATE TABLE auth_user (
    id integer NOT NULL,
    username character varying(30) NOT NULL,
    first_name character varying(30) NOT NULL,
    last_name character varying(30) NOT NULL,
    email character varying(75) NOT NULL,
    password character varying(128) NOT NULL,
    is_staff boolean NOT NULL,
    is_active boolean NOT NULL,
    is_superuser boolean NOT NULL,
    last_login timestamp with time zone NOT NULL,
    date_joined timestamp with time zone NOT NULL
);
CREATE TABLE auth_user_groups (
    id integer NOT NULL,
    user_id integer NOT NULL,
    group_id integer NOT NULL
);
CREATE SEQUENCE auth_user_groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE auth_user_groups_id_seq OWNED BY auth_user_groups.id;
CREATE SEQUENCE auth_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE auth_user_id_seq OWNED BY auth_user.id;
CREATE SEQUENCE banner_category_categoryid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
SET default_with_oids = true;
CREATE TABLE baps_audio (
    audioid integer NOT NULL,
    trackid integer,
    filename character varying(256) NOT NULL
);
CREATE SEQUENCE baps_audio_audioid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE baps_audio_audioid_seq OWNED BY baps_audio.audioid;
CREATE TABLE baps_audiolog (
    audiologid integer NOT NULL,
    serverid integer NOT NULL,
    audioid integer NOT NULL,
    timeplayed timestamp without time zone DEFAULT now() NOT NULL,
    channel integer,
    timestopped timestamp without time zone
);
CREATE SEQUENCE baps_audiolog_audiologid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE baps_audiolog_audiologid_seq OWNED BY baps_audiolog.audiologid;
CREATE TABLE baps_filefolder (
    filefolderid integer NOT NULL,
    workgroup character varying(40) NOT NULL,
    server character varying(40) NOT NULL,
    share character varying(40) NOT NULL,
    username character varying(40),
    password character varying(40),
    public boolean DEFAULT false NOT NULL,
    description character varying(255) NOT NULL,
    owner integer NOT NULL
);
CREATE SEQUENCE baps_filefolder_filefolderid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE baps_filefolder_filefolderid_seq OWNED BY baps_filefolder.filefolderid;
CREATE TABLE baps_fileitem (
    fileitemid integer NOT NULL,
    filename character varying(511) NOT NULL
);
CREATE SEQUENCE baps_fileitem_fileitemid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE baps_fileitem_fileitemid_seq OWNED BY baps_fileitem.fileitemid;
CREATE TABLE baps_item (
    itemid integer NOT NULL,
    listingid integer NOT NULL,
    name1 character varying(255) NOT NULL,
    "position" integer NOT NULL,
    textitemid integer,
    libraryitemid integer,
    fileitemid integer,
    name2 character varying(255),
    CONSTRAINT baps_item_check CHECK ((((((textitemid IS NULL) AND (libraryitemid IS NULL)) AND (fileitemid IS NOT NULL)) OR (((textitemid IS NOT NULL) AND (libraryitemid IS NULL)) AND (fileitemid IS NULL))) OR (((textitemid IS NULL) AND (libraryitemid IS NOT NULL)) AND (fileitemid IS NULL))))
);
CREATE SEQUENCE baps_item_itemid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE baps_item_itemid_seq OWNED BY baps_item.itemid;
CREATE TABLE baps_libraryitem (
    libraryitemid integer NOT NULL,
    recordid integer NOT NULL,
    trackid integer NOT NULL
);
CREATE SEQUENCE baps_libraryitem_libraryitemid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE baps_libraryitem_libraryitemid_seq OWNED BY baps_libraryitem.libraryitemid;
CREATE TABLE baps_listing (
    listingid integer NOT NULL,
    showid integer NOT NULL,
    name character varying(255) NOT NULL,
    channel integer DEFAULT 0 NOT NULL
);
CREATE SEQUENCE baps_listing_listingid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE baps_listing_listingid_seq OWNED BY baps_listing.listingid;
CREATE SEQUENCE baps_personal_collection_unique_id
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 999999
    CACHE 1000;
CREATE TABLE rec_track (
    number smallint NOT NULL,
    title text NOT NULL,
    artist text NOT NULL,
    length time without time zone DEFAULT '00:00:00'::time without time zone NOT NULL,
    genre character(1) DEFAULT 'o'::bpchar NOT NULL,
    intro time without time zone DEFAULT '00:00:00'::time without time zone NOT NULL,
    clean character(1) DEFAULT 'u'::bpchar NOT NULL,
    trackid integer DEFAULT nextval(('"rec_track_trackid_seq"'::text)::regclass) NOT NULL,
    recordid integer NOT NULL,
    digitised boolean DEFAULT false NOT NULL,
    digitisedby integer,
    duration integer,
    lastfm_verified boolean DEFAULT false,
    last_edited_memberid integer,
    last_edited_time timestamp with time zone
);
COMMENT ON COLUMN rec_track.duration IS 'Duration of track in seconds';
COMMENT ON COLUMN rec_track.lastfm_verified IS 'Whether or not the metadata of this track has been verified using the LastFM API';
CREATE VIEW baps_ppl_log AS
    SELECT baps_audiolog.timeplayed, baps_audiolog.timestopped, rec_track.title, rec_track.artist, rec_track.length AS trackduration FROM ((baps_audiolog JOIN baps_audio ON ((baps_audiolog.audioid = baps_audio.audioid))) JOIN rec_track ON ((rec_track.trackid = baps_audio.trackid))) WHERE ((baps_audiolog.timeplayed >= '2012-01-01 00:00:00'::timestamp without time zone) AND (baps_audiolog.timestopped <= '2012-02-01 00:00:00'::timestamp without time zone));
COMMENT ON VIEW baps_ppl_log IS 'Sample query for PPL';
CREATE TABLE baps_server (
    serverid integer NOT NULL,
    servername character varying(50) NOT NULL
);
CREATE SEQUENCE baps_server_serverid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE baps_server_serverid_seq OWNED BY baps_server.serverid;
CREATE TABLE baps_show (
    showid integer NOT NULL,
    userid integer NOT NULL,
    name character varying(255) NOT NULL,
    broadcastdate timestamp without time zone NOT NULL,
    externallinkid integer,
    viewable boolean DEFAULT false NOT NULL
);
CREATE SEQUENCE baps_show_showid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE baps_show_showid_seq OWNED BY baps_show.showid;
CREATE TABLE baps_textitem (
    textitemid integer NOT NULL,
    textinfo text NOT NULL
);
CREATE SEQUENCE baps_textitem_textitemid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE baps_textitem_textitemid_seq OWNED BY baps_textitem.textitemid;
CREATE TABLE baps_user (
    userid integer NOT NULL,
    username character varying(40) NOT NULL,
    admin boolean DEFAULT false NOT NULL,
    quotausage integer DEFAULT 0 NOT NULL,
    quotalimit integer DEFAULT 734003200 NOT NULL,
    shared boolean DEFAULT false NOT NULL,
    signed boolean DEFAULT false NOT NULL,
    userdescription character varying(100)
);
CREATE TABLE baps_user_external (
    userexternalid integer NOT NULL,
    userid integer NOT NULL,
    externalid integer NOT NULL
);
CREATE SEQUENCE baps_user_external_userexternalid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE baps_user_external_userexternalid_seq OWNED BY baps_user_external.userexternalid;
CREATE TABLE baps_user_filefolder (
    userfilefolderid integer NOT NULL,
    userid integer NOT NULL,
    filefolderid integer NOT NULL
);
CREATE SEQUENCE baps_user_filefolder_userfilefolderid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE baps_user_filefolder_userfilefolderid_seq OWNED BY baps_user_filefolder.userfilefolderid;
CREATE SEQUENCE baps_user_userid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE baps_user_userid_seq OWNED BY baps_user.userid;
SET default_with_oids = false;
CREATE TABLE chart (
    chartweek integer NOT NULL,
    lastweek text NOT NULL,
    title text NOT NULL,
    artist text NOT NULL,
    "position" integer NOT NULL
);
COMMENT ON TABLE chart IS 'ury chart rundowns';
COMMENT ON COLUMN chart.chartweek IS 'chart release timestamp';
CREATE TABLE selector (
    selid integer NOT NULL,
    "time" timestamp with time zone DEFAULT now() NOT NULL,
    action integer NOT NULL,
    setby integer NOT NULL,
    CONSTRAINT setbyinrange CHECK (((setby >= 0) AND (setby <= 3)))
);
CREATE VIEW current_studio AS
    SELECT selector.action FROM selector WHERE (selector.action = ANY (ARRAY[4, 5, 6])) ORDER BY selector."time" DESC LIMIT 1;
COMMENT ON VIEW current_studio IS 'Gets the studio that is currently on air
Note: Studio 1 = 4, Studio 2 = 5, Jukebox = 6';
CREATE TABLE gammu (
    "Version" numeric
);
COMMENT ON TABLE gammu IS 'Do not delete - Gammu will break';
SET default_with_oids = true;
CREATE TABLE l_action (
    typeid integer DEFAULT nextval(('"l_action_typeid_seq"'::text)::regclass) NOT NULL,
    descr character varying(255) NOT NULL,
    phpconstant character varying(100) NOT NULL,
    CONSTRAINT l_action_phpconstant_check CHECK (((phpconstant)::text = upper((phpconstant)::text)))
);
COMMENT ON TABLE l_action IS 'Enumerates back end permissions';
COMMENT ON COLUMN l_action.typeid IS 'Surrogate Key';
COMMENT ON COLUMN l_action.descr IS 'What the user sees the permission being called.';
COMMENT ON COLUMN l_action.phpconstant IS 'The name in /members/inc/constants.php';
CREATE SEQUENCE l_action_typeid_seq
    START WITH 200
    INCREMENT BY 1
    MINVALUE 200
    MAXVALUE 10000
    CACHE 1;
CREATE TABLE l_college (
    collegeid integer DEFAULT nextval(('"l_college_collegeid_seq"'::text)::regclass) NOT NULL,
    descr character varying(255) NOT NULL
);
CREATE SEQUENCE l_college_collegeid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 2147483647
    CACHE 1;
CREATE TABLE l_musicinterest (
    typeid integer DEFAULT nextval(('"l_musicinterest_typeid_seq"'::text)::regclass) NOT NULL,
    descr character varying(255) NOT NULL
);
CREATE SEQUENCE l_musicinterest_typeid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 2147483647
    CACHE 1;
SET default_with_oids = false;
CREATE TABLE l_newsfeed (
    feedid integer NOT NULL,
    feedname character varying(30) NOT NULL
);
INSERT INTO l_newsfeed (feedid, feedname) VALUES (1, 'Members News');
INSERT INTO l_newsfeed (feedid, feedname) VALUES (2, 'Tech News');
INSERT INTO l_newsfeed (feedid, feedname) VALUES (3, 'Breaking News');
INSERT INTO l_newsfeed (feedid, feedname) VALUES (4, 'Presenter Information');
COMMENT ON TABLE l_newsfeed IS 'Lookup table for internal news feeds';
CREATE SEQUENCE l_newsfeeds_feedid_seq
    START WITH 5
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE l_newsfeeds_feedid_seq OWNED BY l_newsfeed.feedid;
SET default_with_oids = true;
CREATE TABLE l_presenterstatus (
    presenterstatusid integer NOT NULL,
    descr character varying(40) NOT NULL,
    ordering integer,
    depends integer,
    can_award integer,
    detail character varying
);
COMMENT ON COLUMN l_presenterstatus.can_award IS 'Members with this training status can award other members with this training status.';
CREATE SEQUENCE l_presenterstatus_presenterstatusid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE l_presenterstatus_presenterstatusid_seq OWNED BY l_presenterstatus.presenterstatusid;
CREATE TABLE l_status (
    statusid character(1) NOT NULL,
    descr character varying(255) NOT NULL
);
SET default_with_oids = false;
CREATE TABLE l_subnet (
    subnet cidr NOT NULL,
    iscollege boolean NOT NULL,
    description character varying NOT NULL
);
COMMENT ON TABLE l_subnet IS 'An informed guess (via Gavin) about which subnet covers which colleges.';
CREATE SEQUENCE l_uryinterest_typeid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 2147483647
    CACHE 1;
CREATE TABLE mail_alias_text (
    aliasid integer NOT NULL,
    name character varying NOT NULL,
    dest character varying NOT NULL,
    CONSTRAINT validaliasname CHECK (((name)::text ~ '^([a-zA-Z0-9]|-|_)+(.([a-zA-Z0-9]|-|_)+)*$'::text))
);
COMMENT ON TABLE mail_alias_text IS 'DEPRECATED. See mail.alias and mail.alias_text for replacement.';
CREATE SEQUENCE mail_aliasid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE mail_aliasid_seq OWNED BY mail_alias_text.aliasid;
CREATE TABLE mail_alias_list (
    aliasid integer DEFAULT nextval('mail_aliasid_seq'::regclass) NOT NULL,
    name character varying NOT NULL,
    listid integer NOT NULL,
    CONSTRAINT validaliasname CHECK (((name)::text ~ '^([a-zA-Z0-9]|-|_)+(.([a-zA-Z0-9]|-|_)+)*$'::text))
);
COMMENT ON TABLE mail_alias_list IS 'DEPRECATED. See mail.alias and mail.alias_list for replacement.';
CREATE TABLE mail_alias_member (
    aliasid integer DEFAULT nextval('mail_aliasid_seq'::regclass) NOT NULL,
    name character varying NOT NULL,
    memberid integer NOT NULL,
    CONSTRAINT validaliasname CHECK (((name)::text ~ '^([a-zA-Z0-9]|-|_)+(.([a-zA-Z0-9]|-|_)+)*$'::text))
);
COMMENT ON TABLE mail_alias_member IS 'DEPRECATED. See mail.alias and mail.alias_member for replacement.';
CREATE TABLE mail_alias_officer (
    aliasid integer DEFAULT nextval('mail_aliasid_seq'::regclass) NOT NULL,
    name character varying NOT NULL,
    officerid integer NOT NULL,
    CONSTRAINT validaliasname CHECK (((name)::text ~ '^([a-zA-Z0-9]|-|_)+(.([a-zA-Z0-9]|-|_)+)*$'::text))
);
COMMENT ON TABLE mail_alias_officer IS 'DEPRECATED. See mail.alias and mail.alias_officer for replacement.';
CREATE TABLE mail_list (
    listid integer NOT NULL,
    listname character varying NOT NULL,
    defn text,
    toexim boolean DEFAULT true NOT NULL,
    listaddress character varying,
    subscribable boolean DEFAULT true NOT NULL,
    CONSTRAINT notnulliftoexim CHECK (((toexim AND (listaddress IS NOT NULL)) OR (NOT toexim))),
    CONSTRAINT subscript_or_sql CHECK (((subscribable AND (defn IS NULL)) OR ((NOT subscribable) AND (defn IS NOT NULL)))),
    CONSTRAINT validlistaddress CHECK (((listaddress)::text ~ '^([a-zA-Z0-9]|-|_)+(.([a-zA-Z0-9]|-|_)+)*$'::text))
);
COMMENT ON TABLE mail_list IS 'Definitions of mailing lists';
COMMENT ON COLUMN mail_list.listid IS 'Surrogate Key';
COMMENT ON COLUMN mail_list.listname IS 'Name of the list';
COMMENT ON COLUMN mail_list.defn IS 'A SQL string that returns fname, sname and email address.';
COMMENT ON COLUMN mail_list.toexim IS 'Whether to create a mail alias on the email server for this list.';
COMMENT ON COLUMN mail_list.listaddress IS 'If the list is exported, this is the list''s email address.';
COMMENT ON COLUMN mail_list.subscribable IS 'Whether members can (un)subscribe freely.';
CREATE SEQUENCE mail_list_listid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE mail_list_listid_seq OWNED BY mail_list.listid;
CREATE TABLE mail_subscription (
    memberid integer NOT NULL,
    listid integer NOT NULL
);
COMMENT ON TABLE mail_subscription IS 'If a list is subscribable, then all members here are subscribed.  If a list is not, then all members here are opted out.';
SET default_with_oids = true;
CREATE TABLE member (
    memberid integer DEFAULT nextval(('"member_memberid_seq"'::text)::regclass) NOT NULL,
    fname character varying(255) NOT NULL,
    sname character varying(255) NOT NULL,
    sex character(1) NOT NULL,
    college integer NOT NULL,
    phone character varying(255),
    email character varying(255),
    receive_email boolean DEFAULT true NOT NULL,
    local_name character varying(100),
    local_alias character varying(32),
    account_locked boolean DEFAULT false NOT NULL,
    last_login timestamp with time zone,
    endofcourse timestamp with time zone,
    eduroam character varying,
    usesmtppassword boolean DEFAULT false NOT NULL,
    joined timestamp without time zone DEFAULT now() NOT NULL,
    require_password_change boolean DEFAULT false NOT NULL,
    profile_photo integer,
    bio text,
    auth_provider character varying,
    contract_signed boolean DEFAULT false NOT NULL
);
COMMENT ON COLUMN member.email IS 'If set, this is the user''s contact address. Otherwise, use the eduroam field.';
COMMENT ON COLUMN member.local_name IS 'This column represents the part of the user''s URY email address before the @. When null, the user does not have a URY email account.';
COMMENT ON COLUMN member.endofcourse IS 'This data is inaccurate/useless!';
COMMENT ON COLUMN member.eduroam IS 'An eduroam username (E.G. abc123)';
COMMENT ON COLUMN member.require_password_change IS 'If true, the user is required to change their password on next login.';
COMMENT ON COLUMN member.auth_provider IS 'The MyRadioAuthenticator implementation to use when validating this user. If NULL, try all in turn, and depending on client implementation either ask them to set one or keep it as it is.';
CREATE SEQUENCE member_memberid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 2147483647
    CACHE 1;
SET default_with_oids = false;
CREATE TABLE member_news_feed (
    membernewsfeedid integer NOT NULL,
    newsentryid integer NOT NULL,
    memberid integer NOT NULL,
    seen timestamp without time zone DEFAULT now() NOT NULL
);
COMMENT ON TABLE member_news_feed IS 'Stores when members have seen news feed events';
CREATE SEQUENCE member_news_feed_membernewsfeedid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE member_news_feed_membernewsfeedid_seq OWNED BY member_news_feed.membernewsfeedid;
CREATE SEQUENCE member_office_member_office_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 2147483647
    CACHE 1;
SET default_with_oids = true;
CREATE TABLE member_officer (
    member_officerid integer DEFAULT nextval(('"member_office_member_office_seq"'::text)::regclass) NOT NULL,
    officerid integer NOT NULL,
    memberid integer NOT NULL,
    from_date date NOT NULL,
    till_date date
);
SET default_with_oids = false;
CREATE TABLE member_pass (
    memberid integer NOT NULL,
    password character varying
);
COMMENT ON TABLE member_pass IS 'User password. Access only to be granted to Shibbobleh, Dovecot Users. Exim authenticates via IMAP.';
SET default_with_oids = true;
CREATE TABLE member_presenterstatus (
    memberid integer NOT NULL,
    presenterstatusid integer NOT NULL,
    completeddate timestamp with time zone DEFAULT now() NOT NULL,
    confirmedby integer NOT NULL,
    memberpresenterstatusid integer NOT NULL,
    revokedtime timestamp without time zone,
    revokedby integer
);
CREATE SEQUENCE member_presenterstatus_memberpresenterstatusid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE member_presenterstatus_memberpresenterstatusid_seq OWNED BY member_presenterstatus.memberpresenterstatusid;
CREATE TABLE member_year (
    memberid integer NOT NULL,
    year smallint NOT NULL,
    paid numeric(4,2) DEFAULT 0 NOT NULL
);
SET default_with_oids = false;
CREATE TABLE net_switchport (
    portid integer NOT NULL,
    vlanid integer,
    mac macaddr NOT NULL,
    up boolean NOT NULL
);
COMMENT ON COLUMN net_switchport.portid IS 'Port number from front panel';
COMMENT ON COLUMN net_switchport.vlanid IS 'Untagged vlan for this port';
CREATE TABLE net_switchport_tags (
    portid integer NOT NULL,
    vlanid integer NOT NULL
);
CREATE TABLE net_vlan (
    vlanid integer NOT NULL,
    vlanname character varying NOT NULL
);
COMMENT ON COLUMN net_vlan.vlanid IS 'VLAN Number (not VLAN index)';
CREATE TABLE news_feed (
    newsentryid integer NOT NULL,
    feedid integer,
    memberid integer,
    "timestamp" timestamp without time zone DEFAULT now() NOT NULL,
    content text,
    revoked boolean DEFAULT false NOT NULL
);
COMMENT ON TABLE news_feed IS 'News Feed entries';
CREATE SEQUENCE news_feed_newsentryid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE news_feed_newsentryid_seq OWNED BY news_feed.newsentryid;
CREATE TABLE nipsweb_migrate (
    memberid integer NOT NULL,
    migrated boolean DEFAULT false,
    enforced boolean DEFAULT false
);
COMMENT ON TABLE nipsweb_migrate IS 'Used to migrate users from BAPSWeb to NIPSWeb.';
SET default_with_oids = true;
CREATE TABLE officer (
    officerid integer DEFAULT nextval(('"officer_officerid_seq"'::text)::regclass) NOT NULL,
    officer_name character varying(255) NOT NULL,
    officer_alias character varying(255),
    teamid integer,
    ordering smallint,
    descr character varying(255),
    status character(1) DEFAULT 'c'::bpchar NOT NULL,
    type character(1) DEFAULT 'o'::bpchar,
    CONSTRAINT validalias CHECK (((officer_alias)::text ~ '^([a-zA-Z0-9]|-|_)+(.([a-zA-Z0-9]|-|_)+)*$'::text))
);
COMMENT ON COLUMN officer.type IS '(O)fficer, (A)ssistant Head of Team, (H)ead of Team';
CREATE SEQUENCE officer_officerid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 2147483647
    CACHE 1;
CREATE TABLE rec_cleanlookup (
    clean_code character(1) NOT NULL,
    clean_descr text NOT NULL
);
CREATE TABLE rec_formatlookup (
    format_code character(1) NOT NULL,
    format_descr text NOT NULL
);
CREATE TABLE rec_genrelookup (
    genre_code character(1) NOT NULL,
    genre_descr text NOT NULL
);
SET default_with_oids = false;
CREATE TABLE rec_itunes (
    trackid integer NOT NULL,
    link text,
    preview text,
    image text,
    identifier text
);
COMMENT ON TABLE rec_itunes IS 'itunes affiliation program';
SET default_with_oids = true;
CREATE TABLE rec_labelqueue (
    recordid integer,
    queueid integer DEFAULT nextval(('"rec_labelqueue_queueid_seq"'::text)::regclass) NOT NULL,
    printed boolean DEFAULT false
);
CREATE SEQUENCE rec_labelqueue_queueid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 2147483647
    CACHE 1;
CREATE TABLE rec_locationlookup (
    location_code character(1) NOT NULL,
    location_descr text NOT NULL
);
SET default_with_oids = false;
CREATE TABLE rec_lookup (
    code_type text NOT NULL,
    code character(1) NOT NULL,
    description text NOT NULL
);
COMMENT ON TABLE rec_lookup IS 'Table containing human-readable names for various codes used in the record library.  (Replaces rec_Xlookup for API lookup simplicity.)';
COMMENT ON COLUMN rec_lookup.code_type IS 'The type of code - this replaces the names used in the previous tables.';
COMMENT ON COLUMN rec_lookup.code IS 'The single-character code used in the record library.';
COMMENT ON COLUMN rec_lookup.description IS 'The human-readable description of the code.';
CREATE SEQUENCE rec_lookup_description_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE rec_lookup_description_seq OWNED BY rec_lookup.description;
SET default_with_oids = true;
CREATE TABLE rec_medialookup (
    media_code character(1) NOT NULL,
    media_descr text NOT NULL
);
CREATE TABLE rec_record (
    title text NOT NULL,
    artist text NOT NULL,
    status character(1) DEFAULT 'o'::bpchar NOT NULL,
    media character(1) NOT NULL,
    format character(1) NOT NULL,
    recordlabel text NOT NULL,
    dateadded timestamp with time zone DEFAULT now() NOT NULL,
    datereleased date,
    shelfnumber smallint NOT NULL,
    shelfletter character(1) NOT NULL,
    recordid integer DEFAULT nextval(('"rec_record_recordid_seq"'::text)::regclass) NOT NULL,
    memberid_add integer NOT NULL,
    memberid_lastedit integer,
    datetime_lastedit timestamp with time zone,
    cdid character varying(8),
    location text,
    promoterid integer
);
CREATE SEQUENCE rec_record_recordid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 2147483647
    CACHE 1;
CREATE TABLE rec_statuslookup (
    status_code character(1) NOT NULL,
    status_descr text NOT NULL
);
CREATE SEQUENCE rec_track_trackid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 2147483647
    CACHE 1;
SET default_with_oids = false;
CREATE TABLE rec_trackcorrection (
    correctionid integer NOT NULL,
    trackid integer NOT NULL,
    proposed_title character varying NOT NULL,
    proposed_artist character varying NOT NULL,
    proposed_album_name character varying NOT NULL,
    state character(1) DEFAULT 'p'::bpchar NOT NULL,
    reviewedby integer,
    level integer DEFAULT (-1) NOT NULL
);
COMMENT ON COLUMN rec_trackcorrection.state IS '''a'' Applied, ''r'' Rejected or ''p'' Pending';
CREATE SEQUENCE rec_trackcorrection_correctionid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE rec_trackcorrection_correctionid_seq OWNED BY rec_trackcorrection.correctionid;
CREATE TABLE recommended_listening (
    week integer NOT NULL,
    title text NOT NULL,
    artist text NOT NULL,
    "position" integer NOT NULL
);
COMMENT ON TABLE recommended_listening IS 'Recommended listening lists.';
COMMENT ON COLUMN recommended_listening.week IS 'The timestamp of the week whose list this entry is part of.';
COMMENT ON COLUMN recommended_listening.title IS 'The title of the song that has been recommended.';
COMMENT ON COLUMN recommended_listening.artist IS 'The artist of the song that has been recommended.';
COMMENT ON COLUMN recommended_listening."position" IS 'The position of the item in the list.';
SET search_path = schedule, pg_catalog;
CREATE TABLE show (
    show_id integer NOT NULL,
    show_type_id integer DEFAULT 1 NOT NULL,
    submitted timestamp with time zone,
    memberid integer NOT NULL
);
COMMENT ON TABLE show IS 'This is a very minimal table that ties everything together. The reason it doesn’t contain so much is so that literally everything else can have multiple entries and multiple versions. This lets us know what happened with everything ever, as well as allowing, for allocated seasons and timeslots, overrides for one or two instances over the default.';
COMMENT ON COLUMN show.show_id IS 'A unique identifier for each show';
COMMENT ON COLUMN show.show_type_id IS 'A reference to the type of show';
COMMENT ON COLUMN show.submitted IS 'When the application was submitted. If Null, the application has just been saved by the user (not yet implemented in MyRadio)';
COMMENT ON COLUMN show.memberid IS 'The ID of the user who submitted the application';
CREATE TABLE show_metadata (
    show_metadata_id integer NOT NULL,
    metadata_key_id integer NOT NULL,
    show_id integer NOT NULL,
    metadata_value text,
    effective_from timestamp with time zone,
    memberid integer NOT NULL,
    approvedid integer,
    effective_to timestamp with time zone
);
COMMENT ON TABLE show_metadata IS 'Stores all text-based items of information associated with a show.';
COMMENT ON COLUMN show_metadata.show_metadata_id IS 'A unique identifier for each text item entry';
COMMENT ON COLUMN show_metadata.metadata_key_id IS 'The ID of the type of metadata stored in this table';
COMMENT ON COLUMN show_metadata.show_id IS 'The ID of the show the metadata applies to';
COMMENT ON COLUMN show_metadata.metadata_value IS 'The value of the metadata';
COMMENT ON COLUMN show_metadata.effective_from IS 'The timestamp from which this version of the show metadata became active. A value of NULL means it is not yet active (e.g. pending review)';
COMMENT ON COLUMN show_metadata.memberid IS 'The ID of the member who submitted the updated version of the show metadata';
COMMENT ON COLUMN show_metadata.approvedid IS 'The ID of the member who approved the change to the show metadata item. A value of NULL means not yet approved or this item does not need to be approved.';
COMMENT ON COLUMN show_metadata.effective_to IS 'The timestamp of the period at which this metadatum stops being effective.  If NULL, the metadatum is effective indefinitely from effective_from.';
SET search_path = public, pg_catalog;
SET default_with_oids = true;
SET search_path = schedule, pg_catalog;
SET default_with_oids = false;
CREATE TABLE show_season (
    show_season_id integer NOT NULL,
    show_id integer NOT NULL,
    termid integer NOT NULL,
    submitted timestamp with time zone,
    memberid integer
);
COMMENT ON TABLE show_season IS 'Shows are now divided into Seasons - these are what actually have requested times and individual shows linked to them.';
COMMENT ON COLUMN show_season.show_season_id IS 'A unique identifier for each season';
COMMENT ON COLUMN show_season.show_id IS 'The ID of the show this is a season for';
COMMENT ON COLUMN show_season.termid IS 'The ID for the term this season should be scheduled for';
COMMENT ON COLUMN show_season.submitted IS 'When the application was submitted. If Null, the application has just been saved by the user';
COMMENT ON COLUMN show_season.memberid IS 'The ID of the user that submitted the application';
CREATE TABLE show_season_timeslot (
    show_season_timeslot_id integer NOT NULL,
    show_season_id integer NOT NULL,
    start_time timestamp with time zone NOT NULL,
    memberid integer NOT NULL,
    approvedid integer NOT NULL,
    duration interval NOT NULL
);
COMMENT ON COLUMN show_season_timeslot.duration IS 'The duration of the show, as a time interval.';
CREATE VIEW view_timeslot_legacy AS
    SELECT t1.show_id, t1.starttime, t1.endtime, t1.entryid, t2.summary, t1.timeslotid FROM ((SELECT show_season_timeslot.show_season_timeslot_id AS timeslotid, show_season_timeslot.start_time AS starttime, (show_season_timeslot.start_time + show_season_timeslot.duration) AS endtime, show_season.show_season_id AS entryid, show.show_id FROM show_season_timeslot, show_season, show WHERE ((show_season_timeslot.show_season_id = show_season.show_season_id) AND (show_season.show_id = show.show_id))) t1 LEFT JOIN (SELECT show_metadata.metadata_value AS summary, show_metadata.show_id FROM show_metadata WHERE (show_metadata.metadata_key_id = (SELECT metadata_key.metadata_key_id FROM metadata.metadata_key WHERE ((metadata_key.name)::text = 'title'::text) LIMIT 1))) t2 ON ((t1.show_id = t2.show_id)));
SET search_path = public, pg_catalog;
CREATE VIEW sched_timeslot AS
    SELECT view_timeslot_legacy.show_id, view_timeslot_legacy.starttime, view_timeslot_legacy.endtime, view_timeslot_legacy.entryid, view_timeslot_legacy.summary, view_timeslot_legacy.timeslotid FROM schedule.view_timeslot_legacy;
CREATE TABLE selector_actions (
    action integer NOT NULL,
    description character varying(50) NOT NULL
);
CREATE SEQUENCE selector_actions_action_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE selector_actions_action_seq OWNED BY selector_actions.action;
CREATE SEQUENCE selector_selid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE selector_selid_seq OWNED BY selector.selid;
SET default_with_oids = false;
CREATE TABLE sso_session (
    id character varying(32) NOT NULL,
    data text,
    "timestamp" timestamp without time zone NOT NULL
);
SET default_with_oids = true;
CREATE TABLE strm_useragent (
    useragentid integer NOT NULL,
    useragent character varying(255) NOT NULL
);
CREATE SEQUENCE strm_client_clientid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE strm_client_clientid_seq OWNED BY strm_useragent.useragentid;
CREATE TABLE strm_log (
    logid integer NOT NULL,
    streamid integer NOT NULL,
    useragentid integer NOT NULL,
    starttime timestamp with time zone NOT NULL,
    endtime timestamp with time zone NOT NULL,
    ipaddr inet NOT NULL
);
CREATE SEQUENCE strm_log_logid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE strm_log_logid_seq OWNED BY strm_log.logid;
CREATE TABLE strm_logfile (
    logfileid integer NOT NULL,
    filename character varying(255) NOT NULL,
    lastsize integer
);
CREATE SEQUENCE strm_logfile_logfileid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE strm_logfile_logfileid_seq OWNED BY strm_logfile.logfileid;
CREATE TABLE strm_stream (
    streamid integer NOT NULL,
    streamname character varying(255) NOT NULL
);
CREATE SEQUENCE strm_stream_streamid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE strm_stream_streamid_seq OWNED BY strm_stream.streamid;
CREATE TABLE team (
    teamid integer DEFAULT nextval(('"team_teamid_seq"'::text)::regclass) NOT NULL,
    team_name character varying(255) NOT NULL,
    descr character varying(255),
    local_group character varying(255),
    local_alias character varying(255),
    ordering smallint,
    status character(1) DEFAULT 'c'::bpchar NOT NULL
);
CREATE SEQUENCE team_teamid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 2147483647
    CACHE 1;
SET default_with_oids = false;
CREATE TABLE terms (
    start timestamp with time zone NOT NULL,
    descr character varying(10) NOT NULL,
    finish timestamp with time zone NOT NULL,
    termid integer NOT NULL
);
COMMENT ON TABLE terms IS 'URY university term database.  Must be updated regularly.  Starts and finishes should be midnight UTC on the Monday of Weeks 1 and 11 respectively, NOT MIDNIGHT LOCAL TIME.';
COMMENT ON COLUMN terms.start IS 'Midnight UTC, Monday Week 1';
COMMENT ON COLUMN terms.finish IS 'Midnight UTC, Monday Week 11';
CREATE SEQUENCE terms_termid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE terms_termid_seq OWNED BY terms.termid;
SET search_path = schedule, pg_catalog;
CREATE TABLE block (
    block_id integer NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    tag character varying(100) DEFAULT 'default'::character varying NOT NULL,
    priority integer DEFAULT 0 NOT NULL,
    is_listable boolean DEFAULT false NOT NULL,
    description text DEFAULT ''::text NOT NULL
);
COMMENT ON TABLE block IS 'Schedule blocks, which divide up a schedule (via matching rules covered by other tables) into groups of shows.';
COMMENT ON COLUMN block.block_id IS 'The unique identifier of this schedule block.';
COMMENT ON COLUMN block.name IS 'The human-readable and publicly displayed name of this block.';
COMMENT ON COLUMN block.tag IS 'The machine-readable string identifier used, for example, as the prefix of the CSS classes used to colour this block.';
COMMENT ON COLUMN block.priority IS 'The priority of this block when deciding which block shows fall into.  A lower number indicates a higher priority.';
COMMENT ON COLUMN block.is_listable IS 'If true, the block appears in lists of blocks, allowing people to find shows in that block.';
COMMENT ON COLUMN block.description IS 'A human-readable description of the block.';
CREATE SEQUENCE block_description_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE block_description_seq OWNED BY block.description;
CREATE TABLE block_range_rule (
    block_range_rule_id integer NOT NULL,
    block_id integer NOT NULL,
    start_time interval NOT NULL,
    end_time interval NOT NULL
);
COMMENT ON TABLE block_range_rule IS 'Block rules that associate timeslots falling into given ranges with corresponding blocks.
This is the lowest priority rule type.';
COMMENT ON COLUMN block_range_rule.block_range_rule_id IS 'The unique identifier of this block rule.';
COMMENT ON COLUMN block_range_rule.block_id IS 'The identifier of the block this rule matches.';
COMMENT ON COLUMN block_range_rule.start_time IS 'The start of the range, as an offset from midnight on the day concerned.';
COMMENT ON COLUMN block_range_rule.end_time IS 'The end of this range, as an offset from midnight on the day concerned.';
CREATE SEQUENCE block_range_rule_block_range_rule_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE block_range_rule_block_range_rule_id_seq OWNED BY block_range_rule.block_range_rule_id;
CREATE SEQUENCE block_show_rules_block_show_rule_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
CREATE TABLE block_show_rule (
    block_show_rule_id integer DEFAULT nextval('block_show_rules_block_show_rule_id_seq'::regclass) NOT NULL,
    block_id integer NOT NULL,
    show_id integer NOT NULL
);
COMMENT ON TABLE block_show_rule IS 'A block show rule matches all timeslots of a given show to a given block.
This rule scheme takes precedence over any other scheme except direct timeslot assignment.';
COMMENT ON COLUMN block_show_rule.block_show_rule_id IS 'The unique ID of the matching rule.';
COMMENT ON COLUMN block_show_rule.block_id IS 'The ID of the block this rule assigns a show to.';
COMMENT ON COLUMN block_show_rule.show_id IS 'The ID of the show this rule assigns to a block.';
CREATE SEQUENCE blocks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE blocks_id_seq OWNED BY block.block_id;
CREATE TABLE genre (
    genre_id integer NOT NULL,
    name character varying
);
CREATE SEQUENCE genre_genre_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE genre_genre_id_seq OWNED BY genre.genre_id;
CREATE TABLE location (
    location_id integer NOT NULL,
    location_name character varying
);
COMMENT ON TABLE location IS 'Locations include Studio 1, Studio 2, Outside Broadcast, Production Orifice';
COMMENT ON COLUMN location.location_name IS 'The textual name of the location';
CREATE SEQUENCE location_location_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE location_location_id_seq OWNED BY location.location_id;
CREATE SEQUENCE season_metadata_season_metadata_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
CREATE TABLE season_metadata (
    season_metadata_id integer DEFAULT nextval('season_metadata_season_metadata_id_seq'::regclass) NOT NULL,
    metadata_key_id integer NOT NULL,
    show_season_id integer NOT NULL,
    metadata_value text,
    effective_from timestamp with time zone,
    memberid integer NOT NULL,
    approvedid integer,
    effective_to timestamp with time zone
);
COMMENT ON COLUMN season_metadata.season_metadata_id IS 'A unique identifier for each text item entry';
COMMENT ON COLUMN season_metadata.metadata_key_id IS 'The ID of the type of metadata stored in this table';
COMMENT ON COLUMN season_metadata.show_season_id IS 'The ID of the season the metadata applies to';
COMMENT ON COLUMN season_metadata.metadata_value IS 'The value of the metadata';
COMMENT ON COLUMN season_metadata.effective_from IS 'The timestamp from which this version of the season metadata became active. A value of NULL means it is not yet active (e.g. pending review)';
COMMENT ON COLUMN season_metadata.memberid IS 'The ID of the member who submitted the updated version of the season metadata';
COMMENT ON COLUMN season_metadata.approvedid IS 'The ID of the member who approved the updated version of the season metadata. A value of NULL may mean it is either not approved (See effective_from) or that this metadata type does not need review';
COMMENT ON COLUMN season_metadata.effective_to IS 'The timestamp of the period at which this metadatum stops being effective.  If NULL, the metadatum is effective indefinitely from effective_from.';
CREATE TABLE show_credit (
    show_credit_id integer NOT NULL,
    show_id integer NOT NULL,
    credit_type_id integer NOT NULL,
    creditid integer NOT NULL,
    effective_from timestamp with time zone,
    effective_to timestamp with time zone,
    memberid integer NOT NULL,
    approvedid integer
);
COMMENT ON TABLE show_credit IS 'Associates members to shows';
CREATE SEQUENCE show_credit_show_credit_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE show_credit_show_credit_id_seq OWNED BY show_credit.show_credit_id;
CREATE TABLE show_genre (
    show_genre_id integer NOT NULL,
    show_id integer NOT NULL,
    genre_id integer NOT NULL,
    effective_from timestamp with time zone,
    effective_to timestamp with time zone,
    memberid integer NOT NULL,
    approvedid integer
);
CREATE SEQUENCE show_genre_show_genre_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE show_genre_show_genre_id_seq OWNED BY show_genre.show_genre_id;
CREATE TABLE show_image_metadata (
    memberid integer,
    approvedid integer,
    effective_from timestamp with time zone,
    effective_to timestamp with time zone,
    metadata_key_id integer NOT NULL,
    metadata_value text NOT NULL,
    show_id integer,
    show_image_metadata_id integer NOT NULL
);
CREATE SEQUENCE show_image_metadata_show_image_metadata_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE show_image_metadata_show_image_metadata_id_seq OWNED BY show_image_metadata.show_image_metadata_id;
CREATE TABLE show_location (
    show_location_id integer NOT NULL,
    show_id integer NOT NULL,
    location_id integer NOT NULL,
    effective_from timestamp with time zone,
    effective_to timestamp with time zone,
    memberid integer NOT NULL,
    approvedid integer
);
CREATE SEQUENCE show_location_show_location_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE show_location_show_location_id_seq OWNED BY show_location.show_location_id;
CREATE SEQUENCE show_metadata_show_metadata_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE show_metadata_show_metadata_id_seq OWNED BY show_metadata.show_metadata_id;
CREATE TABLE show_podcast_link (
    podcast_id integer NOT NULL,
    show_id integer NOT NULL
);
CREATE TABLE show_season_requested_time (
    show_season_requested_time_id integer NOT NULL,
    requested_day integer NOT NULL,
    start_time integer NOT NULL,
    preference smallint DEFAULT 1 NOT NULL,
    duration interval NOT NULL,
    show_season_id integer NOT NULL
);
COMMENT ON TABLE show_season_requested_time IS 'Stores a list of times requested for a season with their preference. When a season is applied for, users are expected to make at least three preferences';
COMMENT ON COLUMN show_season_requested_time.show_season_requested_time_id IS 'A unique identifier for each show season requested time';
COMMENT ON COLUMN show_season_requested_time.requested_day IS 'The day that the show starts on.';
COMMENT ON COLUMN show_season_requested_time.start_time IS 'The requested time for the show to start. Stored as seconds since midnight.';
COMMENT ON COLUMN show_season_requested_time.preference IS 'A lower preference number means it is more preferred.';
CREATE SEQUENCE show_season_requested_time_show_season_requested_time_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE show_season_requested_time_show_season_requested_time_id_seq OWNED BY show_season_requested_time.show_season_requested_time_id;
CREATE TABLE show_season_requested_week (
    show_season_requested_week_id integer NOT NULL,
    show_season_id integer NOT NULL,
    week smallint NOT NULL
);
COMMENT ON TABLE show_season_requested_week IS 'Stores a list of weeks for which a season wishes to be scheduled. Uses values 1-10 for weeks within a standard term.';
CREATE SEQUENCE show_season_requested_week_show_season_requested_week_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE show_season_requested_week_show_season_requested_week_id_seq OWNED BY show_season_requested_week.show_season_requested_week_id;
CREATE SEQUENCE show_season_show_season_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE show_season_show_season_id_seq OWNED BY show_season.show_season_id;
CREATE VIEW show_season_text_metadata AS
    SELECT season_metadata.season_metadata_id, season_metadata.metadata_key_id, season_metadata.show_season_id, season_metadata.metadata_value, season_metadata.effective_from, season_metadata.memberid, season_metadata.approvedid, season_metadata.effective_to FROM season_metadata;
CREATE VIEW show_season_timeslot_credit AS
    SELECT show_credit.show_credit_id AS show_season_timeslot_credit_id, show_season_timeslot.show_season_timeslot_id, show_credit.credit_type_id, show_credit.creditid, show_credit.effective_from, show_credit.effective_to, show_credit.memberid, show_credit.approvedid FROM ((show_credit NATURAL JOIN show_season) NATURAL JOIN show_season_timeslot);
COMMENT ON VIEW show_season_timeslot_credit IS 'Removed:
  WHERE show_credit.effective_from IS NOT NULL AND show_credit.effective_from <= show_season_timeslot.start_time AND (show_credit.effective_to IS NULL OR show_credit.effective_to >= (show_season_timeslot.start_time + show_season_timeslot.duration));';
CREATE SEQUENCE show_season_timeslot_show_season_timeslot_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE show_season_timeslot_show_season_timeslot_id_seq OWNED BY show_season_timeslot.show_season_timeslot_id;
CREATE TABLE timeslot_metadata (
    metadata_key_id integer NOT NULL,
    show_season_timeslot_id integer NOT NULL,
    metadata_value text,
    effective_from timestamp with time zone,
    memberid integer NOT NULL,
    approvedid integer,
    timeslot_metadata_id integer NOT NULL,
    effective_to timestamp with time zone
);
COMMENT ON COLUMN timeslot_metadata.timeslot_metadata_id IS 'The unique numeric ID of this metadatum.';
CREATE VIEW show_season_timeslot_text_metadata AS
    SELECT timeslot_metadata.metadata_key_id, timeslot_metadata.show_season_timeslot_id, timeslot_metadata.metadata_value, timeslot_metadata.effective_from, timeslot_metadata.memberid, timeslot_metadata.approvedid, timeslot_metadata.timeslot_metadata_id, timeslot_metadata.effective_to FROM timeslot_metadata;
CREATE SEQUENCE show_show_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE show_show_id_seq OWNED BY show.show_id;
CREATE VIEW show_text_metadata AS
    SELECT show_metadata.show_metadata_id, show_metadata.metadata_key_id, show_metadata.show_id, show_metadata.metadata_value, show_metadata.effective_from, show_metadata.memberid, show_metadata.approvedid, show_metadata.effective_to FROM show_metadata;
CREATE TABLE show_type (
    show_type_id integer NOT NULL,
    name character varying,
    public boolean DEFAULT true NOT NULL,
    has_showdb_entry boolean DEFAULT true NOT NULL,
    description text DEFAULT ''::text NOT NULL,
    can_be_messaged boolean DEFAULT false NOT NULL,
    is_collapsible boolean DEFAULT false
);
COMMENT ON TABLE show_type IS 'Stores the possible types of “show”. Only one of these will actually be a show, but all can be entered on the Scheduling system. This allows support for Demos and Training (even though training is in a lecture - it can be added as Outside Broadcast) as well as John Wakefield’s pre-recording in Studio 2 and other general Studio 2 goings on, since it doesn’t often get used for shows.';
COMMENT ON COLUMN show_type.show_type_id IS 'A unique identifier for each Show type';
COMMENT ON COLUMN show_type.name IS 'The name of the show type';
COMMENT ON COLUMN show_type.public IS 'Whether or not this show type is visible on the public schedule';
COMMENT ON COLUMN show_type.has_showdb_entry IS 'If true, shows of this type and their season/timeslot descendents will have a publicly accessible showdb entry.';
COMMENT ON COLUMN show_type.description IS 'A human-readable description of the show type.';
COMMENT ON COLUMN show_type.can_be_messaged IS 'If true, the show can be messaged via the website.';
CREATE SEQUENCE show_type_show_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE show_type_show_type_id_seq OWNED BY show_type.show_type_id;
CREATE SEQUENCE timeslot_metadata_timeslot_metadata_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE timeslot_metadata_timeslot_metadata_id_seq OWNED BY timeslot_metadata.timeslot_metadata_id;
SET search_path = sis2, pg_catalog;
CREATE TABLE commtype (
    commtypeid integer NOT NULL,
    descr character varying(16) NOT NULL
);
INSERT INTO commtype (commtypeid, descr) VALUES (1, 'Email');
INSERT INTO commtype (commtypeid, descr) VALUES (2, 'SMS');
INSERT INTO commtype (commtypeid, descr) VALUES (3, 'Website');
INSERT INTO commtype (commtypeid, descr) VALUES (4, 'Request');
INSERT INTO commtype (commtypeid, descr) VALUES (5, 'Mobile Site');
CREATE SEQUENCE commtype_commtypeid_seq
    START WITH 6
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE commtype_commtypeid_seq OWNED BY commtype.commtypeid;
CREATE TABLE config (
    setting character varying(100) NOT NULL,
    value character varying(100)
);
CREATE TABLE member_options (
    memberid integer NOT NULL,
    helptab boolean DEFAULT true
);
CREATE TABLE member_signin (
    member_signin_id integer NOT NULL,
    memberid integer NOT NULL,
    sign_time timestamp without time zone DEFAULT now() NOT NULL,
    signerid integer NOT NULL,
    show_season_timeslot_id integer NOT NULL
);
COMMENT ON TABLE member_signin IS 'Stores members signing into their shows';
COMMENT ON COLUMN member_signin.member_signin_id IS 'Unique Identifier for this signin event';
COMMENT ON COLUMN member_signin.memberid IS 'The ID of the user signed in';
COMMENT ON COLUMN member_signin.sign_time IS 'The time the user was signed in';
COMMENT ON COLUMN member_signin.signerid IS 'The ID of the user who signed the user in';
COMMENT ON COLUMN member_signin.show_season_timeslot_id IS 'The Timeslot that the users are signing in to';
CREATE SEQUENCE member_signin_member_signin_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE member_signin_member_signin_id_seq OWNED BY member_signin.member_signin_id;
CREATE TABLE messages (
    commid integer NOT NULL,
    timeslotid integer NOT NULL,
    commtypeid integer NOT NULL,
    sender character varying(64),
    date timestamp with time zone DEFAULT now() NOT NULL,
    subject character varying,
    content text,
    statusid integer NOT NULL,
    comm_source character varying(15)
);
COMMENT ON TABLE messages IS 'Stores SIS comm messages';
CREATE SEQUENCE messages_commid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE messages_commid_seq OWNED BY messages.commid;
CREATE TABLE statustype (
    statusid integer NOT NULL,
    descr character varying(8)
);
INSERT INTO statustype (statusid, descr) VALUES (1, 'Unread');
INSERT INTO statustype (statusid, descr) VALUES (2, 'Read');
INSERT INTO statustype (statusid, descr) VALUES (3, 'Deleted');
INSERT INTO statustype (statusid, descr) VALUES (4, 'Junk');
INSERT INTO statustype (statusid, descr) VALUES (5, 'Abusive');
CREATE SEQUENCE statustype_statusid_seq
    START WITH 6
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE statustype_statusid_seq OWNED BY statustype.statusid;
SET search_path = tracklist, pg_catalog;
CREATE TABLE selbaps (
    selaction integer NOT NULL,
    bapsloc integer NOT NULL
);
COMMENT ON TABLE selbaps IS 'Marries selector actions with BAPS servers';
CREATE TABLE source (
    sourceid character(1) NOT NULL,
    source text NOT NULL
);
COMMENT ON TABLE source IS 'Lookup table for source of track';
CREATE TABLE state (
    stateid character(1) NOT NULL,
    state text NOT NULL
);
COMMENT ON TABLE state IS 'State lookup table';
CREATE TABLE track_notrec (
    audiologid integer NOT NULL,
    artist text NOT NULL,
    album text,
    label text,
    trackno integer,
    track text NOT NULL,
    length timestamp without time zone
);
COMMENT ON TABLE track_notrec IS 'Retrieves tracks not entered in the database';
CREATE TABLE track_rec (
    audiologid integer NOT NULL,
    recordid integer NOT NULL,
    trackid integer
);
COMMENT ON TABLE track_rec IS 'Retrieves tracks from central database';
CREATE TABLE tracklist (
    source character(1) NOT NULL,
    timestart timestamp without time zone DEFAULT now() NOT NULL,
    timestop timestamp without time zone,
    state character(1),
    timeslotid integer,
    audiologid integer NOT NULL,
    bapsaudioid integer
);
COMMENT ON TABLE tracklist IS 'Main tracklisting table. Things spur off of this';
CREATE SEQUENCE tracklist_audiologid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE tracklist_audiologid_seq OWNED BY tracklist.audiologid;
SET search_path = uryplayer, pg_catalog;
CREATE TABLE podcast (
    memberid integer,
    approvedid integer,
    submitted timestamp with time zone,
    podcast_id integer NOT NULL,
    file character varying(100),
    suspended boolean DEFAULT false NOT NULL
);
CREATE TABLE podcast_credit (
    memberid integer,
    approvedid integer,
    effective_from timestamp with time zone,
    effective_to timestamp with time zone,
    credit_type_id integer NOT NULL,
    creditid integer NOT NULL,
    podcast_credit_id integer NOT NULL,
    podcast_id integer NOT NULL
);
CREATE SEQUENCE podcast_credit_podcast_credit_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE podcast_credit_podcast_credit_id_seq OWNED BY podcast_credit.podcast_credit_id;
CREATE TABLE podcast_image_metadata (
    memberid integer,
    approvedid integer,
    effective_from timestamp with time zone,
    effective_to timestamp with time zone,
    metadata_key_id integer NOT NULL,
    metadata_value character varying(100) NOT NULL,
    podcast_image_metadata_id integer NOT NULL,
    podcast_id integer
);
CREATE SEQUENCE podcast_image_metadata_podcast_image_metadata_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE podcast_image_metadata_podcast_image_metadata_id_seq OWNED BY podcast_image_metadata.podcast_image_metadata_id;
CREATE TABLE podcast_metadata (
    memberid integer,
    approvedid integer,
    effective_from timestamp with time zone,
    effective_to timestamp with time zone,
    metadata_key_id integer NOT NULL,
    metadata_value text NOT NULL,
    podcast_metadata_id integer NOT NULL,
    podcast_id integer
);
CREATE SEQUENCE podcast_metadata_podcast_metadata_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE podcast_metadata_podcast_metadata_id_seq OWNED BY podcast_metadata.podcast_metadata_id;
CREATE TABLE podcast_package_entry (
    memberid integer,
    approvedid integer,
    effective_from timestamp with time zone,
    effective_to timestamp with time zone,
    package_id integer NOT NULL,
    podcast_id integer NOT NULL,
    podcast_package_entry_id integer NOT NULL
);
CREATE SEQUENCE podcast_package_entry_podcast_package_entry_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE podcast_package_entry_podcast_package_entry_id_seq OWNED BY podcast_package_entry.podcast_package_entry_id;
CREATE SEQUENCE podcast_podcast_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE podcast_podcast_id_seq OWNED BY podcast.podcast_id;
CREATE VIEW podcast_text_metadata AS
    SELECT podcast_metadata.memberid, podcast_metadata.approvedid, podcast_metadata.effective_from, podcast_metadata.effective_to, podcast_metadata.metadata_key_id, podcast_metadata.metadata_value, podcast_metadata.podcast_metadata_id, podcast_metadata.podcast_id FROM podcast_metadata;
SET search_path = webcam, pg_catalog;
CREATE TABLE memberviews (
    memberid integer NOT NULL,
    timer integer DEFAULT 0 NOT NULL
);
CREATE TABLE streams (
    streamid integer NOT NULL,
    streamname character varying NOT NULL,
    liveurl character varying NOT NULL,
    staticurl character varying NOT NULL
);
COMMENT ON TABLE streams IS 'Stores a list of streams available on MyRadio';
CREATE SEQUENCE streams_streamid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE streams_streamid_seq OWNED BY streams.streamid;
SET search_path = website, pg_catalog;
CREATE TABLE banner (
    banner_id integer NOT NULL,
    alt text NOT NULL,
    image character varying(100) NOT NULL,
    target character varying(200) NOT NULL,
    banner_type_id integer NOT NULL,
    photoid integer
);
COMMENT ON COLUMN banner.photoid IS 'The corresponding MyRadio Photo ID, if one exists.';
CREATE SEQUENCE banner_banner_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE banner_banner_id_seq OWNED BY banner.banner_id;
CREATE TABLE banner_campaign (
    memberid integer,
    approvedid integer,
    effective_from timestamp with time zone NOT NULL,
    effective_to timestamp with time zone,
    banner_campaign_id integer NOT NULL,
    banner_location_id integer NOT NULL,
    banner_id integer NOT NULL
);
CREATE SEQUENCE banner_campaign_banner_campaign_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE banner_campaign_banner_campaign_id_seq OWNED BY banner_campaign.banner_campaign_id;
CREATE TABLE banner_location (
    name character varying(50) NOT NULL,
    description text NOT NULL,
    banner_location_id integer NOT NULL
);
CREATE SEQUENCE banner_location_banner_location_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE banner_location_banner_location_id_seq OWNED BY banner_location.banner_location_id;
CREATE TABLE banner_timeslot (
    id integer NOT NULL,
    memberid integer,
    approvedid integer,
    "order" integer NOT NULL,
    banner_campaign_id integer NOT NULL,
    day smallint NOT NULL,
    start_time time without time zone NOT NULL,
    end_time time without time zone,
    CONSTRAINT banner_timeslot_day_check CHECK ((day >= 0)),
    CONSTRAINT banner_timeslot_order_check CHECK (("order" >= 0))
);
COMMENT ON COLUMN banner_timeslot.start_time IS 'The time of day when the banner will start rotating.';
COMMENT ON COLUMN banner_timeslot.end_time IS 'The time of day when the banner will stop rotating.';
CREATE SEQUENCE banner_timeslot_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE banner_timeslot_id_seq OWNED BY banner_timeslot.id;
CREATE TABLE banner_type (
    name character varying(50) NOT NULL,
    description text NOT NULL,
    banner_type_id integer NOT NULL
);
CREATE SEQUENCE banner_type_banner_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE banner_type_banner_type_id_seq OWNED BY banner_type.banner_type_id;
SET search_path = bapsplanner, pg_catalog;
ALTER TABLE ONLY managed_items ALTER COLUMN manageditemid SET DEFAULT nextval('managed_items_manageditemid_seq'::regclass);
ALTER TABLE ONLY managed_playlists ALTER COLUMN managedplaylistid SET DEFAULT nextval('managed_playlists_managedplaylistid_seq'::regclass);
ALTER TABLE ONLY timeslot_change_ops ALTER COLUMN timeslot_change_set_id SET DEFAULT nextval('timeslot_change_ops_timeslot_change_set_id_seq'::regclass);
ALTER TABLE ONLY timeslot_items ALTER COLUMN timeslot_item_id SET DEFAULT nextval('timeslot_items_timeslot_item_id_seq'::regclass);
SET search_path = jukebox, pg_catalog;
ALTER TABLE ONLY playlist_entries ALTER COLUMN entryid SET DEFAULT nextval('playlist_entries_entryid_seq'::regclass);
ALTER TABLE ONLY request ALTER COLUMN request_id SET DEFAULT nextval('request_request_id_seq'::regclass);
ALTER TABLE ONLY silence_log ALTER COLUMN silenceid SET DEFAULT nextval('silence_log_silenceid_seq'::regclass);
SET search_path = mail, pg_catalog;
ALTER TABLE ONLY alias ALTER COLUMN alias_id SET DEFAULT nextval('alias_alias_id_seq'::regclass);
ALTER TABLE ONLY email ALTER COLUMN email_id SET DEFAULT nextval('emails_email_id_seq'::regclass);
SET search_path = metadata, pg_catalog;
ALTER TABLE ONLY metadata_key ALTER COLUMN metadata_key_id SET DEFAULT nextval('metadata_key_metadata_key_id_seq'::regclass);
ALTER TABLE ONLY package ALTER COLUMN package_id SET DEFAULT nextval('package_package_id_seq'::regclass);
ALTER TABLE ONLY package_image_metadata ALTER COLUMN package_image_metadata_id SET DEFAULT nextval('package_image_metadata_package_image_metadata_id_seq'::regclass);
ALTER TABLE ONLY package_text_metadata ALTER COLUMN package_text_metadata_id SET DEFAULT nextval('package_text_metadata_package_text_metadata_id_seq'::regclass);
SET search_path = music, pg_catalog;
ALTER TABLE ONLY chart_release ALTER COLUMN chart_release_id SET DEFAULT nextval('chart_release_chart_release_id_seq'::regclass);
ALTER TABLE ONLY chart_row ALTER COLUMN chart_row_id SET DEFAULT nextval('chart_row_chart_row_id_seq'::regclass);
ALTER TABLE ONLY chart_type ALTER COLUMN chart_type_id SET DEFAULT nextval('chart_type_chart_type_id_seq'::regclass);
SET search_path = myury, pg_catalog;
ALTER TABLE ONLY act_permission ALTER COLUMN actpermissionid SET DEFAULT nextval('act_permission_actpermissionid_seq'::regclass);
ALTER TABLE ONLY actions ALTER COLUMN actionid SET DEFAULT nextval('actions_actionid_seq'::regclass);
ALTER TABLE ONLY api_class_map ALTER COLUMN api_map_id SET DEFAULT nextval('api_class_map_api_map_id_seq'::regclass);
ALTER TABLE ONLY api_method_auth ALTER COLUMN api_method_auth_id SET DEFAULT nextval('api_method_auth_api_method_auth_id_seq'::regclass);
ALTER TABLE ONLY award_categories ALTER COLUMN awardid SET DEFAULT nextval('award_categories_awardid_seq'::regclass);
ALTER TABLE ONLY award_member ALTER COLUMN awardmemberid SET DEFAULT nextval('award_member_awardmemberid_seq'::regclass);
ALTER TABLE ONLY modules ALTER COLUMN moduleid SET DEFAULT nextval('modules_moduleid_seq'::regclass);
ALTER TABLE ONLY photos ALTER COLUMN photoid SET DEFAULT nextval('photos_photoid_seq'::regclass);
ALTER TABLE ONLY services ALTER COLUMN serviceid SET DEFAULT nextval('services_serviceid_seq'::regclass);
ALTER TABLE ONLY services_versions ALTER COLUMN serviceversionid SET DEFAULT nextval('services_versions_serviceversionid_seq'::regclass);
ALTER TABLE ONLY api_key_log ALTER COLUMN api_log_id SET DEFAULT nextval('api_key_log_api_log_id_seq'::regclass);
SET search_path = people, pg_catalog;
ALTER TABLE ONLY credit_type ALTER COLUMN credit_type_id SET DEFAULT nextval('"schedule.showcredittype_id_seq"'::regclass);
ALTER TABLE ONLY group_root_role ALTER COLUMN group_root_role_id SET DEFAULT nextval('group_root_role_group_root_role_id_seq'::regclass);
ALTER TABLE ONLY group_type ALTER COLUMN group_type_id SET DEFAULT nextval('group_type_group_type_id_seq'::regclass);
ALTER TABLE ONLY quote ALTER COLUMN quote_id SET DEFAULT nextval('quote_quote_id_seq'::regclass);
ALTER TABLE ONLY role ALTER COLUMN role_id SET DEFAULT nextval('roles_id_seq'::regclass);
ALTER TABLE ONLY role_inheritance ALTER COLUMN role_inheritance_id SET DEFAULT nextval('role_inheritance_role_inheritance_id_seq'::regclass);
ALTER TABLE ONLY role_text_metadata ALTER COLUMN role_text_metadata_id SET DEFAULT nextval('role_metadata_role_metadata_id_seq'::regclass);
ALTER TABLE ONLY role_visibility ALTER COLUMN role_visibility_id SET DEFAULT nextval('types_id_seq'::regclass);
SET search_path = public, pg_catalog;
ALTER TABLE ONLY auth_group ALTER COLUMN id SET DEFAULT nextval('auth_group_id_seq'::regclass);
ALTER TABLE ONLY auth_user ALTER COLUMN id SET DEFAULT nextval('auth_user_id_seq'::regclass);
ALTER TABLE ONLY auth_user_groups ALTER COLUMN id SET DEFAULT nextval('auth_user_groups_id_seq'::regclass);
ALTER TABLE ONLY baps_audio ALTER COLUMN audioid SET DEFAULT nextval('baps_audio_audioid_seq'::regclass);
ALTER TABLE ONLY baps_audiolog ALTER COLUMN audiologid SET DEFAULT nextval('baps_audiolog_audiologid_seq'::regclass);
ALTER TABLE ONLY baps_filefolder ALTER COLUMN filefolderid SET DEFAULT nextval('baps_filefolder_filefolderid_seq'::regclass);
ALTER TABLE ONLY baps_fileitem ALTER COLUMN fileitemid SET DEFAULT nextval('baps_fileitem_fileitemid_seq'::regclass);
ALTER TABLE ONLY baps_item ALTER COLUMN itemid SET DEFAULT nextval('baps_item_itemid_seq'::regclass);
ALTER TABLE ONLY baps_libraryitem ALTER COLUMN libraryitemid SET DEFAULT nextval('baps_libraryitem_libraryitemid_seq'::regclass);
ALTER TABLE ONLY baps_listing ALTER COLUMN listingid SET DEFAULT nextval('baps_listing_listingid_seq'::regclass);
ALTER TABLE ONLY baps_server ALTER COLUMN serverid SET DEFAULT nextval('baps_server_serverid_seq'::regclass);
ALTER TABLE ONLY baps_show ALTER COLUMN showid SET DEFAULT nextval('baps_show_showid_seq'::regclass);
ALTER TABLE ONLY baps_textitem ALTER COLUMN textitemid SET DEFAULT nextval('baps_textitem_textitemid_seq'::regclass);
ALTER TABLE ONLY baps_user ALTER COLUMN userid SET DEFAULT nextval('baps_user_userid_seq'::regclass);
ALTER TABLE ONLY baps_user_external ALTER COLUMN userexternalid SET DEFAULT nextval('baps_user_external_userexternalid_seq'::regclass);
ALTER TABLE ONLY baps_user_filefolder ALTER COLUMN userfilefolderid SET DEFAULT nextval('baps_user_filefolder_userfilefolderid_seq'::regclass);
ALTER TABLE ONLY l_newsfeed ALTER COLUMN feedid SET DEFAULT nextval('l_newsfeeds_feedid_seq'::regclass);
ALTER TABLE ONLY l_presenterstatus ALTER COLUMN presenterstatusid SET DEFAULT nextval('l_presenterstatus_presenterstatusid_seq'::regclass);
ALTER TABLE ONLY mail_alias_text ALTER COLUMN aliasid SET DEFAULT nextval('mail_aliasid_seq'::regclass);
ALTER TABLE ONLY mail_list ALTER COLUMN listid SET DEFAULT nextval('mail_list_listid_seq'::regclass);
ALTER TABLE ONLY member_news_feed ALTER COLUMN membernewsfeedid SET DEFAULT nextval('member_news_feed_membernewsfeedid_seq'::regclass);
ALTER TABLE ONLY member_presenterstatus ALTER COLUMN memberpresenterstatusid SET DEFAULT nextval('member_presenterstatus_memberpresenterstatusid_seq'::regclass);
ALTER TABLE ONLY news_feed ALTER COLUMN newsentryid SET DEFAULT nextval('news_feed_newsentryid_seq'::regclass);
ALTER TABLE ONLY rec_trackcorrection ALTER COLUMN correctionid SET DEFAULT nextval('rec_trackcorrection_correctionid_seq'::regclass);
ALTER TABLE ONLY selector ALTER COLUMN selid SET DEFAULT nextval('selector_selid_seq'::regclass);
ALTER TABLE ONLY selector_actions ALTER COLUMN action SET DEFAULT nextval('selector_actions_action_seq'::regclass);
ALTER TABLE ONLY strm_log ALTER COLUMN logid SET DEFAULT nextval('strm_log_logid_seq'::regclass);
ALTER TABLE ONLY strm_logfile ALTER COLUMN logfileid SET DEFAULT nextval('strm_logfile_logfileid_seq'::regclass);
ALTER TABLE ONLY strm_stream ALTER COLUMN streamid SET DEFAULT nextval('strm_stream_streamid_seq'::regclass);
ALTER TABLE ONLY strm_useragent ALTER COLUMN useragentid SET DEFAULT nextval('strm_client_clientid_seq'::regclass);
ALTER TABLE ONLY terms ALTER COLUMN termid SET DEFAULT nextval('terms_termid_seq'::regclass);
SET search_path = schedule, pg_catalog;
ALTER TABLE ONLY block ALTER COLUMN block_id SET DEFAULT nextval('blocks_id_seq'::regclass);
ALTER TABLE ONLY block_range_rule ALTER COLUMN block_range_rule_id SET DEFAULT nextval('block_range_rule_block_range_rule_id_seq'::regclass);
ALTER TABLE ONLY genre ALTER COLUMN genre_id SET DEFAULT nextval('genre_genre_id_seq'::regclass);
ALTER TABLE ONLY location ALTER COLUMN location_id SET DEFAULT nextval('location_location_id_seq'::regclass);
ALTER TABLE ONLY show ALTER COLUMN show_id SET DEFAULT nextval('show_show_id_seq'::regclass);
ALTER TABLE ONLY show_credit ALTER COLUMN show_credit_id SET DEFAULT nextval('show_credit_show_credit_id_seq'::regclass);
ALTER TABLE ONLY show_genre ALTER COLUMN show_genre_id SET DEFAULT nextval('show_genre_show_genre_id_seq'::regclass);
ALTER TABLE ONLY show_image_metadata ALTER COLUMN show_image_metadata_id SET DEFAULT nextval('show_image_metadata_show_image_metadata_id_seq'::regclass);
ALTER TABLE ONLY show_location ALTER COLUMN show_location_id SET DEFAULT nextval('show_location_show_location_id_seq'::regclass);
ALTER TABLE ONLY show_metadata ALTER COLUMN show_metadata_id SET DEFAULT nextval('show_metadata_show_metadata_id_seq'::regclass);
ALTER TABLE ONLY show_season ALTER COLUMN show_season_id SET DEFAULT nextval('show_season_show_season_id_seq'::regclass);
ALTER TABLE ONLY show_season_requested_time ALTER COLUMN show_season_requested_time_id SET DEFAULT nextval('show_season_requested_time_show_season_requested_time_id_seq'::regclass);
ALTER TABLE ONLY show_season_requested_week ALTER COLUMN show_season_requested_week_id SET DEFAULT nextval('show_season_requested_week_show_season_requested_week_id_seq'::regclass);
ALTER TABLE ONLY show_season_timeslot ALTER COLUMN show_season_timeslot_id SET DEFAULT nextval('show_season_timeslot_show_season_timeslot_id_seq'::regclass);
ALTER TABLE ONLY show_type ALTER COLUMN show_type_id SET DEFAULT nextval('show_type_show_type_id_seq'::regclass);
ALTER TABLE ONLY timeslot_metadata ALTER COLUMN timeslot_metadata_id SET DEFAULT nextval('timeslot_metadata_timeslot_metadata_id_seq'::regclass);
SET search_path = sis2, pg_catalog;
ALTER TABLE ONLY commtype ALTER COLUMN commtypeid SET DEFAULT nextval('commtype_commtypeid_seq'::regclass);
ALTER TABLE ONLY member_signin ALTER COLUMN member_signin_id SET DEFAULT nextval('member_signin_member_signin_id_seq'::regclass);
ALTER TABLE ONLY messages ALTER COLUMN commid SET DEFAULT nextval('messages_commid_seq'::regclass);
ALTER TABLE ONLY statustype ALTER COLUMN statusid SET DEFAULT nextval('statustype_statusid_seq'::regclass);
SET search_path = tracklist, pg_catalog;
ALTER TABLE ONLY tracklist ALTER COLUMN audiologid SET DEFAULT nextval('tracklist_audiologid_seq'::regclass);
SET search_path = uryplayer, pg_catalog;
ALTER TABLE ONLY podcast ALTER COLUMN podcast_id SET DEFAULT nextval('podcast_podcast_id_seq'::regclass);
ALTER TABLE ONLY podcast_credit ALTER COLUMN podcast_credit_id SET DEFAULT nextval('podcast_credit_podcast_credit_id_seq'::regclass);
ALTER TABLE ONLY podcast_image_metadata ALTER COLUMN podcast_image_metadata_id SET DEFAULT nextval('podcast_image_metadata_podcast_image_metadata_id_seq'::regclass);
ALTER TABLE ONLY podcast_metadata ALTER COLUMN podcast_metadata_id SET DEFAULT nextval('podcast_metadata_podcast_metadata_id_seq'::regclass);
ALTER TABLE ONLY podcast_package_entry ALTER COLUMN podcast_package_entry_id SET DEFAULT nextval('podcast_package_entry_podcast_package_entry_id_seq'::regclass);
SET search_path = webcam, pg_catalog;
ALTER TABLE ONLY streams ALTER COLUMN streamid SET DEFAULT nextval('streams_streamid_seq'::regclass);
SET search_path = website, pg_catalog;
ALTER TABLE ONLY banner ALTER COLUMN banner_id SET DEFAULT nextval('banner_banner_id_seq'::regclass);
ALTER TABLE ONLY banner_campaign ALTER COLUMN banner_campaign_id SET DEFAULT nextval('banner_campaign_banner_campaign_id_seq'::regclass);
ALTER TABLE ONLY banner_location ALTER COLUMN banner_location_id SET DEFAULT nextval('banner_location_banner_location_id_seq'::regclass);
ALTER TABLE ONLY banner_timeslot ALTER COLUMN id SET DEFAULT nextval('banner_timeslot_id_seq'::regclass);
ALTER TABLE ONLY banner_type ALTER COLUMN banner_type_id SET DEFAULT nextval('banner_type_banner_type_id_seq'::regclass);

--------------
-- Add constraints and keys
-- These were missing from the initial dump for some reason
--------------
SET search_path = bapsplanner, pg_catalog;

--
-- Name: auto_playlists_pkey; Type: CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY auto_playlists
    ADD CONSTRAINT auto_playlists_pkey PRIMARY KEY (auto_playlist_id);


--
-- Name: client_ids_pkey; Type: CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY client_ids
    ADD CONSTRAINT client_ids_pkey PRIMARY KEY (client_id);


--
-- Name: managed_items_pkey; Type: CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY managed_items
    ADD CONSTRAINT managed_items_pkey PRIMARY KEY (manageditemid);


--
-- Name: managed_playlists_folder_key; Type: CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY managed_playlists
    ADD CONSTRAINT managed_playlists_folder_key UNIQUE (folder);


--
-- Name: managed_playlists_name_key; Type: CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY managed_playlists
    ADD CONSTRAINT managed_playlists_name_key UNIQUE (name);


--
-- Name: managed_playlists_pkey; Type: CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY managed_playlists
    ADD CONSTRAINT managed_playlists_pkey PRIMARY KEY (managedplaylistid);


--
-- Name: secure_play_token_pkey; Type: CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY secure_play_token
    ADD CONSTRAINT secure_play_token_pkey PRIMARY KEY (sessionid, memberid, "timestamp", trackid);


--
-- Name: timeslot_change_ops_pkey; Type: CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY timeslot_change_ops
    ADD CONSTRAINT timeslot_change_ops_pkey PRIMARY KEY (timeslot_change_set_id);


--
-- Name: timeslot_items_pkey; Type: CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY timeslot_items
    ADD CONSTRAINT timeslot_items_pkey PRIMARY KEY (timeslot_item_id);

SET search_path = jukebox, pg_catalog;

--
-- Name: playlist_entries_pkey; Type: CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlist_entries
    ADD CONSTRAINT playlist_entries_pkey PRIMARY KEY (playlistid, trackid, revision_added);


--
-- Name: playlist_revisions_pkey; Type: CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlist_revisions
    ADD CONSTRAINT playlist_revisions_pkey PRIMARY KEY (playlistid, revisionid);


--
-- Name: playlist_timeslot_pkey; Type: CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlist_timeslot
    ADD CONSTRAINT playlist_timeslot_pkey PRIMARY KEY (id);


--
-- Name: playlists_pkey; Type: CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlists
    ADD CONSTRAINT playlists_pkey PRIMARY KEY (playlistid);


--
-- Name: request_pkey; Type: CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY request
    ADD CONSTRAINT request_pkey PRIMARY KEY (request_id);


--
-- Name: silence_log_pkey; Type: CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY silence_log
    ADD CONSTRAINT silence_log_pkey PRIMARY KEY (silenceid);


--
-- Name: track_blacklist_pkey; Type: CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY track_blacklist
    ADD CONSTRAINT track_blacklist_pkey PRIMARY KEY (trackid);


SET search_path = mail, pg_catalog;

--
-- Name: alias_list_pkey; Type: CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY alias_list
    ADD CONSTRAINT alias_list_pkey PRIMARY KEY (alias_id, destination);


--
-- Name: alias_member_pkey; Type: CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY alias_member
    ADD CONSTRAINT alias_member_pkey PRIMARY KEY (alias_id, destination);


--
-- Name: alias_officer_pkey; Type: CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY alias_officer
    ADD CONSTRAINT alias_officer_pkey PRIMARY KEY (alias_id, destination);


--
-- Name: alias_pkey; Type: CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY alias
    ADD CONSTRAINT alias_pkey PRIMARY KEY (alias_id);


--
-- Name: alias_source_key; Type: CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY alias
    ADD CONSTRAINT alias_source_key UNIQUE (source);


--
-- Name: alias_text_pkey; Type: CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY alias_text
    ADD CONSTRAINT alias_text_pkey PRIMARY KEY (alias_id, destination);


--
-- Name: email_recipient_list_pkey; Type: CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY email_recipient_list
    ADD CONSTRAINT email_recipient_list_pkey PRIMARY KEY (email_id, listid);


--
-- Name: email_recipient_user_pkey; Type: CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY email_recipient_member
    ADD CONSTRAINT email_recipient_user_pkey PRIMARY KEY (email_id, memberid);


--
-- Name: emails_pkey; Type: CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY email
    ADD CONSTRAINT emails_pkey PRIMARY KEY (email_id);


SET search_path = metadata, pg_catalog;

--
-- Name: metadata_key_name_key; Type: CONSTRAINT; Schema: metadata
--

ALTER TABLE ONLY metadata_key
    ADD CONSTRAINT metadata_key_name_key UNIQUE (name);


--
-- Name: metadata_key_pkey; Type: CONSTRAINT; Schema: metadata
--

ALTER TABLE ONLY metadata_key
    ADD CONSTRAINT metadata_key_pkey PRIMARY KEY (metadata_key_id);


--
-- Name: package_image_metadata_pkey; Type: CONSTRAINT; Schema: metadata
--

ALTER TABLE ONLY package_image_metadata
    ADD CONSTRAINT package_image_metadata_pkey PRIMARY KEY (package_image_metadata_id);


--
-- Name: package_pkey; Type: CONSTRAINT; Schema: metadata
--

ALTER TABLE ONLY package
    ADD CONSTRAINT package_pkey PRIMARY KEY (package_id);


--
-- Name: package_text_metadata_pkey; Type: CONSTRAINT; Schema: metadata
--

ALTER TABLE ONLY package_text_metadata
    ADD CONSTRAINT package_text_metadata_pkey PRIMARY KEY (package_text_metadata_id);


SET search_path = music, pg_catalog;

--
-- Name: chart_release_pkey; Type: CONSTRAINT; Schema: music
--

ALTER TABLE ONLY chart_release
    ADD CONSTRAINT chart_release_pkey PRIMARY KEY (chart_release_id);


--
-- Name: chart_row_chart_row_id_key; Type: CONSTRAINT; Schema: music
--

ALTER TABLE ONLY chart_row
    ADD CONSTRAINT chart_row_chart_row_id_key UNIQUE (chart_row_id, "position");


--
-- Name: chart_row_pkey; Type: CONSTRAINT; Schema: music
--

ALTER TABLE ONLY chart_row
    ADD CONSTRAINT chart_row_pkey PRIMARY KEY (chart_row_id);


--
-- Name: chart_type_pkey; Type: CONSTRAINT; Schema: music
--

ALTER TABLE ONLY chart_type
    ADD CONSTRAINT chart_type_pkey PRIMARY KEY (chart_type_id);


SET search_path = myury, pg_catalog;

--
-- Name: act_permission_pkey; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY act_permission
    ADD CONSTRAINT act_permission_pkey PRIMARY KEY (actpermissionid);


--
-- Name: act_permission_serviceid_key; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY act_permission
    ADD CONSTRAINT act_permission_serviceid_key UNIQUE (serviceid, moduleid, actionid, typeid);


--
-- Name: actions_moduleid_key; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY actions
    ADD CONSTRAINT actions_moduleid_key UNIQUE (moduleid, name);


--
-- Name: actions_pkey; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY actions
    ADD CONSTRAINT actions_pkey PRIMARY KEY (actionid);


--
-- Name: api_class_map_api_name_key; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY api_class_map
    ADD CONSTRAINT api_class_map_api_name_key UNIQUE (api_name);


--
-- Name: api_class_map_class_name_key; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY api_class_map
    ADD CONSTRAINT api_class_map_class_name_key UNIQUE (class_name);


--
-- Name: api_class_map_pkey; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY api_class_map
    ADD CONSTRAINT api_class_map_pkey PRIMARY KEY (api_map_id);


--
-- Name: api_key_auth_pkey; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY api_key_auth
    ADD CONSTRAINT api_key_auth_pkey PRIMARY KEY (key_string, typeid);

--
-- Name: api_key_pkey; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY api_key
    ADD CONSTRAINT api_key_pkey PRIMARY KEY (key_string);


--
-- Name: api_method_auth_class_name_method_name_typeid_key; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY api_method_auth
    ADD CONSTRAINT api_method_auth_class_name_method_name_typeid_key UNIQUE (class_name, method_name, typeid);


--
-- Name: api_method_auth_pkey; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY api_method_auth
    ADD CONSTRAINT api_method_auth_pkey PRIMARY KEY (api_method_auth_id);


--
-- Name: award_categories_name_key; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY award_categories
    ADD CONSTRAINT award_categories_name_key UNIQUE (name);


--
-- Name: award_categories_pkey; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY award_categories
    ADD CONSTRAINT award_categories_pkey PRIMARY KEY (awardid);


--
-- Name: award_member_pkey; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY award_member
    ADD CONSTRAINT award_member_pkey PRIMARY KEY (awardmemberid);


--
-- Name: modules_pkey; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY modules
    ADD CONSTRAINT modules_pkey PRIMARY KEY (moduleid);


--
-- Name: modules_serviceid_key; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY modules
    ADD CONSTRAINT modules_serviceid_key UNIQUE (serviceid, name);


--
-- Name: photos_pkey; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY photos
    ADD CONSTRAINT photos_pkey PRIMARY KEY (photoid);


--
-- Name: services_name_key; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY services
    ADD CONSTRAINT services_name_key UNIQUE (name);


--
-- Name: services_pkey; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY services
    ADD CONSTRAINT services_pkey PRIMARY KEY (serviceid);


--
-- Name: services_versions_pkey; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY services_versions
    ADD CONSTRAINT services_versions_pkey PRIMARY KEY (serviceversionid);


--
-- Name: services_versions_serviceid_key; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY services_versions
    ADD CONSTRAINT services_versions_serviceid_key UNIQUE (serviceid, version);


--
-- Name: services_versions_serviceid_key1; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY services_versions
    ADD CONSTRAINT services_versions_serviceid_key1 UNIQUE (serviceid, path);


--
-- Name: services_versions_users_pkey; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY services_versions_member
    ADD CONSTRAINT services_versions_users_pkey PRIMARY KEY (memberid, serviceversionid);


--
-- Name: single_login_token_pkey; Type: CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY password_reset_token
    ADD CONSTRAINT single_login_token_pkey PRIMARY KEY (token);


SET search_path = people, pg_catalog;

--
-- Name: group_root_role_pkey; Type: CONSTRAINT; Schema: people
--

ALTER TABLE ONLY group_root_role
    ADD CONSTRAINT group_root_role_pkey PRIMARY KEY (group_root_role_id);


--
-- Name: group_root_role_role_id_id_key; Type: CONSTRAINT; Schema: people
--

ALTER TABLE ONLY group_root_role
    ADD CONSTRAINT group_root_role_role_id_id_key UNIQUE (role_id_id);


--
-- Name: group_type_pkey; Type: CONSTRAINT; Schema: people
--

ALTER TABLE ONLY group_type
    ADD CONSTRAINT group_type_pkey PRIMARY KEY (group_type_id);


--
-- Name: metadata_pkey; Type: CONSTRAINT; Schema: people
--

ALTER TABLE ONLY metadata
    ADD CONSTRAINT metadata_pkey PRIMARY KEY (roleid, key);


--
-- Name: quote_pkey; Type: CONSTRAINT; Schema: people
--

ALTER TABLE ONLY quote
    ADD CONSTRAINT quote_pkey PRIMARY KEY (quote_id);


--
-- Name: role_inheritance_pkey; Type: CONSTRAINT; Schema: people
--

ALTER TABLE ONLY role_inheritance
    ADD CONSTRAINT role_inheritance_pkey PRIMARY KEY (role_inheritance_id);


--
-- Name: role_metadata_pkey; Type: CONSTRAINT; Schema: people
--

ALTER TABLE ONLY role_text_metadata
    ADD CONSTRAINT role_metadata_pkey PRIMARY KEY (role_text_metadata_id);


--
-- Name: roles_pkey; Type: CONSTRAINT; Schema: people
--

ALTER TABLE ONLY role
    ADD CONSTRAINT roles_pkey PRIMARY KEY (role_id);


--
-- Name: schedule.showcredittype_pkey; Type: CONSTRAINT; Schema: people
--

ALTER TABLE ONLY credit_type
    ADD CONSTRAINT "schedule.showcredittype_pkey" PRIMARY KEY (credit_type_id);


--
-- Name: types_name_key; Type: CONSTRAINT; Schema: people
--

ALTER TABLE ONLY role_visibility
    ADD CONSTRAINT types_name_key UNIQUE (name);


--
-- Name: types_pkey; Type: CONSTRAINT; Schema: people
--

ALTER TABLE ONLY role_visibility
    ADD CONSTRAINT types_pkey PRIMARY KEY (role_visibility_id);


SET search_path = public, pg_catalog;

--
-- Name: auth_group_name_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_group
    ADD CONSTRAINT auth_group_name_key UNIQUE (name);


--
-- Name: auth_group_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_group
    ADD CONSTRAINT auth_group_pkey PRIMARY KEY (id);


--
-- Name: auth_officer_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_officer
    ADD CONSTRAINT auth_officer_pkey PRIMARY KEY (officerid, lookupid);


--
-- Name: auth_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth
    ADD CONSTRAINT auth_pkey PRIMARY KEY (memberid, lookupid, starttime);


--
-- Name: auth_subnet_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_subnet
    ADD CONSTRAINT auth_subnet_pkey PRIMARY KEY (typeid, subnet);


--
-- Name: auth_trainingstatus_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_trainingstatus
    ADD CONSTRAINT auth_trainingstatus_pkey PRIMARY KEY (typeid, presenterstatusid);


--
-- Name: auth_user_groups_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_user_groups
    ADD CONSTRAINT auth_user_groups_pkey PRIMARY KEY (id);


--
-- Name: auth_user_groups_user_id_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_user_groups
    ADD CONSTRAINT auth_user_groups_user_id_key UNIQUE (user_id, group_id);


--
-- Name: auth_user_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_user
    ADD CONSTRAINT auth_user_pkey PRIMARY KEY (id);


--
-- Name: auth_user_username_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_user
    ADD CONSTRAINT auth_user_username_key UNIQUE (username);


--
-- Name: baps_audio_filename_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_audio
    ADD CONSTRAINT baps_audio_filename_key UNIQUE (filename);


--
-- Name: baps_audio_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_audio
    ADD CONSTRAINT baps_audio_pkey PRIMARY KEY (audioid);


--
-- Name: baps_audio_trackid_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_audio
    ADD CONSTRAINT baps_audio_trackid_key UNIQUE (trackid);


--
-- Name: baps_audiolog_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_audiolog
    ADD CONSTRAINT baps_audiolog_pkey PRIMARY KEY (audiologid);


--
-- Name: baps_filefolder_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_filefolder
    ADD CONSTRAINT baps_filefolder_pkey PRIMARY KEY (filefolderid);

ALTER TABLE baps_filefolder CLUSTER ON baps_filefolder_pkey;


--
-- Name: baps_filefolder_workgroup_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_filefolder
    ADD CONSTRAINT baps_filefolder_workgroup_key UNIQUE (workgroup, server, share);


--
-- Name: baps_fileitem_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_fileitem
    ADD CONSTRAINT baps_fileitem_pkey PRIMARY KEY (fileitemid);

ALTER TABLE baps_fileitem CLUSTER ON baps_fileitem_pkey;


--
-- Name: baps_item_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_item
    ADD CONSTRAINT baps_item_pkey PRIMARY KEY (itemid);

ALTER TABLE baps_item CLUSTER ON baps_item_pkey;


--
-- Name: baps_libraryitem_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_libraryitem
    ADD CONSTRAINT baps_libraryitem_pkey PRIMARY KEY (libraryitemid);

ALTER TABLE baps_libraryitem CLUSTER ON baps_libraryitem_pkey;


--
-- Name: baps_listing_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_listing
    ADD CONSTRAINT baps_listing_pkey PRIMARY KEY (listingid);


--
-- Name: baps_server_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_server
    ADD CONSTRAINT baps_server_pkey PRIMARY KEY (serverid);


--
-- Name: baps_server_servername_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_server
    ADD CONSTRAINT baps_server_servername_key UNIQUE (servername);


--
-- Name: baps_show_name_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_show
    ADD CONSTRAINT baps_show_name_key UNIQUE (name, userid);


--
-- Name: baps_show_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_show
    ADD CONSTRAINT baps_show_pkey PRIMARY KEY (showid);

ALTER TABLE baps_show CLUSTER ON baps_show_pkey;


--
-- Name: baps_textitem_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_textitem
    ADD CONSTRAINT baps_textitem_pkey PRIMARY KEY (textitemid);

ALTER TABLE baps_textitem CLUSTER ON baps_textitem_pkey;


--
-- Name: baps_user_external_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_user_external
    ADD CONSTRAINT baps_user_external_pkey PRIMARY KEY (userexternalid);


--
-- Name: baps_user_external_userid_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_user_external
    ADD CONSTRAINT baps_user_external_userid_key UNIQUE (userid, externalid);


--
-- Name: baps_user_filefolder_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_user_filefolder
    ADD CONSTRAINT baps_user_filefolder_pkey PRIMARY KEY (userfilefolderid);


--
-- Name: baps_user_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_user
    ADD CONSTRAINT baps_user_pkey PRIMARY KEY (userid);


--
-- Name: baps_user_username_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_user
    ADD CONSTRAINT baps_user_username_key UNIQUE (username);

ALTER TABLE baps_user CLUSTER ON baps_user_username_key;


--
-- Name: chart_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY chart
    ADD CONSTRAINT chart_pkey PRIMARY KEY (chartweek, "position");


--
-- Name: l_action_descr_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY l_action
    ADD CONSTRAINT l_action_descr_key UNIQUE (descr);


--
-- Name: l_action_phpconstant_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY l_action
    ADD CONSTRAINT l_action_phpconstant_key UNIQUE (phpconstant);


--
-- Name: l_action_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY l_action
    ADD CONSTRAINT l_action_pkey PRIMARY KEY (typeid);


--
-- Name: l_college_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY l_college
    ADD CONSTRAINT l_college_pkey PRIMARY KEY (collegeid);


--
-- Name: l_musicinterest_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY l_musicinterest
    ADD CONSTRAINT l_musicinterest_pkey PRIMARY KEY (typeid);


--
-- Name: l_newsfeeds_feedname_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY l_newsfeed
    ADD CONSTRAINT l_newsfeeds_feedname_key UNIQUE (feedname);


--
-- Name: l_newsfeeds_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY l_newsfeed
    ADD CONSTRAINT l_newsfeeds_pkey PRIMARY KEY (feedid);


--
-- Name: l_presenterstatus_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY l_presenterstatus
    ADD CONSTRAINT l_presenterstatus_pkey PRIMARY KEY (presenterstatusid);


--
-- Name: l_status_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY l_status
    ADD CONSTRAINT l_status_pkey PRIMARY KEY (statusid);


--
-- Name: l_subnet_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY l_subnet
    ADD CONSTRAINT l_subnet_pkey PRIMARY KEY (subnet);


--
-- Name: mail_alias_list_name_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_alias_list
    ADD CONSTRAINT mail_alias_list_name_key UNIQUE (name, listid);


--
-- Name: mail_alias_list_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_alias_list
    ADD CONSTRAINT mail_alias_list_pkey PRIMARY KEY (aliasid);


--
-- Name: mail_alias_member_name_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_alias_member
    ADD CONSTRAINT mail_alias_member_name_key UNIQUE (name, memberid);


--
-- Name: mail_alias_member_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_alias_member
    ADD CONSTRAINT mail_alias_member_pkey PRIMARY KEY (aliasid);


--
-- Name: mail_alias_officer_name_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_alias_officer
    ADD CONSTRAINT mail_alias_officer_name_key UNIQUE (name, officerid);


--
-- Name: mail_alias_officer_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_alias_officer
    ADD CONSTRAINT mail_alias_officer_pkey PRIMARY KEY (aliasid);


--
-- Name: mail_alias_text_name_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_alias_text
    ADD CONSTRAINT mail_alias_text_name_key UNIQUE (name, dest);


--
-- Name: mail_alias_text_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_alias_text
    ADD CONSTRAINT mail_alias_text_pkey PRIMARY KEY (aliasid);


--
-- Name: mail_list_listaddress_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_list
    ADD CONSTRAINT mail_list_listaddress_key UNIQUE (listaddress);


--
-- Name: mail_list_listname_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_list
    ADD CONSTRAINT mail_list_listname_key UNIQUE (listname);


--
-- Name: mail_list_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_list
    ADD CONSTRAINT mail_list_pkey PRIMARY KEY (listid);


--
-- Name: member_eduroam_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member
    ADD CONSTRAINT member_eduroam_key UNIQUE (eduroam);


--
-- Name: member_email_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member
    ADD CONSTRAINT member_email_key UNIQUE (email);


--
-- Name: member_local_alias_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member
    ADD CONSTRAINT member_local_alias_key UNIQUE (local_alias);


--
-- Name: member_local_name_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member
    ADD CONSTRAINT member_local_name_key UNIQUE (local_name);


--
-- Name: member_mail_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_subscription
    ADD CONSTRAINT member_mail_pkey PRIMARY KEY (memberid, listid);


--
-- Name: member_news_feed_memberid_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_news_feed
    ADD CONSTRAINT member_news_feed_memberid_key UNIQUE (memberid, newsentryid);


--
-- Name: member_news_feed_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_news_feed
    ADD CONSTRAINT member_news_feed_pkey PRIMARY KEY (membernewsfeedid);


--
-- Name: member_officer_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_officer
    ADD CONSTRAINT member_officer_pkey PRIMARY KEY (member_officerid);


--
-- Name: member_pass_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_pass
    ADD CONSTRAINT member_pass_pkey PRIMARY KEY (memberid);


--
-- Name: member_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member
    ADD CONSTRAINT member_pkey PRIMARY KEY (memberid);


--
-- Name: member_presenterstatus_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_presenterstatus
    ADD CONSTRAINT member_presenterstatus_pkey PRIMARY KEY (memberpresenterstatusid);


--
-- Name: member_year_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_year
    ADD CONSTRAINT member_year_pkey PRIMARY KEY (memberid, year);


--
-- Name: net_switchport_tags_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY net_switchport_tags
    ADD CONSTRAINT net_switchport_tags_pkey PRIMARY KEY (portid, vlanid);


--
-- Name: net_switchports_mac_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY net_switchport
    ADD CONSTRAINT net_switchports_mac_key UNIQUE (mac);


--
-- Name: net_switchports_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY net_switchport
    ADD CONSTRAINT net_switchports_pkey PRIMARY KEY (portid);


--
-- Name: net_vlan_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY net_vlan
    ADD CONSTRAINT net_vlan_pkey PRIMARY KEY (vlanid);


--
-- Name: news_feed_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY news_feed
    ADD CONSTRAINT news_feed_pkey PRIMARY KEY (newsentryid);


--
-- Name: nipsweb_migrate_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY nipsweb_migrate
    ADD CONSTRAINT nipsweb_migrate_pkey PRIMARY KEY (memberid);


--
-- Name: officer_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY officer
    ADD CONSTRAINT officer_pkey PRIMARY KEY (officerid);


--
-- Name: rec_cleanlookup_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_cleanlookup
    ADD CONSTRAINT rec_cleanlookup_pkey PRIMARY KEY (clean_code);


--
-- Name: rec_formatlookup_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_formatlookup
    ADD CONSTRAINT rec_formatlookup_pkey PRIMARY KEY (format_code);


--
-- Name: rec_genrelookup_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_genrelookup
    ADD CONSTRAINT rec_genrelookup_pkey PRIMARY KEY (genre_code);


--
-- Name: rec_itunes_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_itunes
    ADD CONSTRAINT rec_itunes_pkey PRIMARY KEY (trackid);


--
-- Name: rec_labelqueue_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_labelqueue
    ADD CONSTRAINT rec_labelqueue_pkey PRIMARY KEY (queueid);


--
-- Name: rec_locationlookup_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_locationlookup
    ADD CONSTRAINT rec_locationlookup_pkey PRIMARY KEY (location_code);


--
-- Name: rec_lookup_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_lookup
    ADD CONSTRAINT rec_lookup_pkey PRIMARY KEY (code_type, code);


--
-- Name: rec_medialookup_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_medialookup
    ADD CONSTRAINT rec_medialookup_pkey PRIMARY KEY (media_code);


--
-- Name: rec_record_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_record
    ADD CONSTRAINT rec_record_pkey PRIMARY KEY (recordid);


--
-- Name: rec_statuslookup_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_statuslookup
    ADD CONSTRAINT rec_statuslookup_pkey PRIMARY KEY (status_code);


--
-- Name: rec_track_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_track
    ADD CONSTRAINT rec_track_pkey PRIMARY KEY (trackid);


--
-- Name: rec_track_trackid_recordid_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_track
    ADD CONSTRAINT rec_track_trackid_recordid_key UNIQUE (trackid, recordid);


--
-- Name: rec_trackcorrection_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_trackcorrection
    ADD CONSTRAINT rec_trackcorrection_pkey PRIMARY KEY (correctionid);


--
-- Name: recommended_listening_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY recommended_listening
    ADD CONSTRAINT recommended_listening_pkey PRIMARY KEY (week, "position");


--
-- Name: selector_actions_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY selector_actions
    ADD CONSTRAINT selector_actions_pkey PRIMARY KEY (action);


--
-- Name: selector_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY selector
    ADD CONSTRAINT selector_pkey PRIMARY KEY (selid);


--
-- Name: sso_session_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY sso_session
    ADD CONSTRAINT sso_session_pkey PRIMARY KEY (id, "timestamp");


--
-- Name: strm_client_clientname_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY strm_useragent
    ADD CONSTRAINT strm_client_clientname_key UNIQUE (useragent);


--
-- Name: strm_client_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY strm_useragent
    ADD CONSTRAINT strm_client_pkey PRIMARY KEY (useragentid);


--
-- Name: strm_log_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY strm_log
    ADD CONSTRAINT strm_log_pkey PRIMARY KEY (logid);


--
-- Name: strm_logfile_filename_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY strm_logfile
    ADD CONSTRAINT strm_logfile_filename_key UNIQUE (filename);


--
-- Name: strm_logfile_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY strm_logfile
    ADD CONSTRAINT strm_logfile_pkey PRIMARY KEY (logfileid);


--
-- Name: strm_stream_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY strm_stream
    ADD CONSTRAINT strm_stream_pkey PRIMARY KEY (streamid);


--
-- Name: strm_stream_streamname_key; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY strm_stream
    ADD CONSTRAINT strm_stream_streamname_key UNIQUE (streamname);


--
-- Name: team_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY team
    ADD CONSTRAINT team_pkey PRIMARY KEY (teamid);


--
-- Name: terms_pkey; Type: CONSTRAINT; Schema: public
--

ALTER TABLE ONLY terms
    ADD CONSTRAINT terms_pkey PRIMARY KEY (termid);


SET search_path = schedule, pg_catalog;

--
-- Name: block_direct_rules_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY block_show_rule
    ADD CONSTRAINT block_direct_rules_pkey PRIMARY KEY (block_show_rule_id);


--
-- Name: block_range_rule_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY block_range_rule
    ADD CONSTRAINT block_range_rule_pkey PRIMARY KEY (block_range_rule_id);


--
-- Name: blocks_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY block
    ADD CONSTRAINT blocks_pkey PRIMARY KEY (block_id);


--
-- Name: genre_name_key; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY genre
    ADD CONSTRAINT genre_name_key UNIQUE (name);


--
-- Name: genre_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY genre
    ADD CONSTRAINT genre_pkey PRIMARY KEY (genre_id);


--
-- Name: location_location_name_key; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY location
    ADD CONSTRAINT location_location_name_key UNIQUE (location_name);


--
-- Name: location_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY location
    ADD CONSTRAINT location_pkey PRIMARY KEY (location_id);


--
-- Name: season_metadata_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY season_metadata
    ADD CONSTRAINT season_metadata_pkey PRIMARY KEY (season_metadata_id);


--
-- Name: show_credit_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_credit
    ADD CONSTRAINT show_credit_pkey PRIMARY KEY (show_credit_id);


--
-- Name: show_genre_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_genre
    ADD CONSTRAINT show_genre_pkey PRIMARY KEY (show_genre_id);


--
-- Name: show_image_metadata_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_image_metadata
    ADD CONSTRAINT show_image_metadata_pkey PRIMARY KEY (show_image_metadata_id);


--
-- Name: show_location_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_location
    ADD CONSTRAINT show_location_pkey PRIMARY KEY (show_location_id);


--
-- Name: show_metadata_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_metadata
    ADD CONSTRAINT show_metadata_pkey PRIMARY KEY (show_metadata_id);


--
-- Name: show_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show
    ADD CONSTRAINT show_pkey PRIMARY KEY (show_id);


--
-- Name: show_podcast_link_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_podcast_link
    ADD CONSTRAINT show_podcast_link_pkey PRIMARY KEY (podcast_id, show_id);


--
-- Name: show_season_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_season
    ADD CONSTRAINT show_season_pkey PRIMARY KEY (show_season_id);


--
-- Name: show_season_requested_time_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_season_requested_time
    ADD CONSTRAINT show_season_requested_time_pkey PRIMARY KEY (show_season_requested_time_id);


--
-- Name: show_season_requested_week_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_season_requested_week
    ADD CONSTRAINT show_season_requested_week_pkey PRIMARY KEY (show_season_requested_week_id);


--
-- Name: show_season_requested_week_show_season_id_key; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_season_requested_week
    ADD CONSTRAINT show_season_requested_week_show_season_id_key UNIQUE (show_season_id, week);


--
-- Name: show_season_timeslot_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_season_timeslot
    ADD CONSTRAINT show_season_timeslot_pkey PRIMARY KEY (show_season_timeslot_id);


--
-- Name: show_season_timeslot_start_time_key; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_season_timeslot
    ADD CONSTRAINT show_season_timeslot_start_time_key UNIQUE (start_time);


--
-- Name: show_type_name_key; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_type
    ADD CONSTRAINT show_type_name_key UNIQUE (name);


--
-- Name: show_type_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_type
    ADD CONSTRAINT show_type_pkey PRIMARY KEY (show_type_id);


--
-- Name: timeslot_metadata_pkey; Type: CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY timeslot_metadata
    ADD CONSTRAINT timeslot_metadata_pkey PRIMARY KEY (timeslot_metadata_id);


SET search_path = sis2, pg_catalog;

--
-- Name: sis_type_pkey; Type: CONSTRAINT; Schema: sis2
--

ALTER TABLE ONLY commtype
    ADD CONSTRAINT sis_type_pkey PRIMARY KEY (commtypeid);


--
-- Name: config_pkey; Type: CONSTRAINT; Schema: sis2
--

ALTER TABLE ONLY config
    ADD CONSTRAINT config_pkey PRIMARY KEY (setting);


--
-- Name: member_options_pkey; Type: CONSTRAINT; Schema: sis2
--

ALTER TABLE ONLY member_options
    ADD CONSTRAINT member_options_pkey PRIMARY KEY (memberid);


--
-- Name: member_signin_memberid_show_season_timeslot_id_key; Type: CONSTRAINT; Schema: sis2
--

ALTER TABLE ONLY member_signin
    ADD CONSTRAINT member_signin_memberid_show_season_timeslot_id_key UNIQUE (memberid, show_season_timeslot_id);


--
-- Name: member_signin_pkey; Type: CONSTRAINT; Schema: sis2
--

ALTER TABLE ONLY member_signin
    ADD CONSTRAINT member_signin_pkey PRIMARY KEY (member_signin_id);


--
-- Name: sis_status_pkey; Type: CONSTRAINT; Schema: sis2
--

ALTER TABLE ONLY statustype
    ADD CONSTRAINT sis_status_pkey PRIMARY KEY (statusid);


SET search_path = tracklist, pg_catalog;

--
-- Name: pri_track_notrec; Type: CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY track_notrec
    ADD CONSTRAINT pri_track_notrec PRIMARY KEY (audiologid);


--
-- Name: pri_track_rec; Type: CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY track_rec
    ADD CONSTRAINT pri_track_rec PRIMARY KEY (audiologid);


--
-- Name: source_source_key; Type: CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY source
    ADD CONSTRAINT source_source_key UNIQUE (source);


--
-- Name: source_sourceid_key; Type: CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY source
    ADD CONSTRAINT source_sourceid_key UNIQUE (sourceid);


--
-- Name: state_stateid_key; Type: CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY state
    ADD CONSTRAINT state_stateid_key UNIQUE (stateid);


--
-- Name: tracklist_pkey; Type: CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY tracklist
    ADD CONSTRAINT tracklist_pkey PRIMARY KEY (audiologid);


SET search_path = uryplayer, pg_catalog;

--
-- Name: podcast_credit_pkey; Type: CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_credit
    ADD CONSTRAINT podcast_credit_pkey PRIMARY KEY (podcast_credit_id);


--
-- Name: podcast_image_metadata_pkey; Type: CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_image_metadata
    ADD CONSTRAINT podcast_image_metadata_pkey PRIMARY KEY (podcast_image_metadata_id);


--
-- Name: podcast_metadata_pkey; Type: CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_metadata
    ADD CONSTRAINT podcast_metadata_pkey PRIMARY KEY (podcast_metadata_id);


--
-- Name: podcast_package_entry_pkey; Type: CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_package_entry
    ADD CONSTRAINT podcast_package_entry_pkey PRIMARY KEY (podcast_package_entry_id);


--
-- Name: podcast_pkey; Type: CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast
    ADD CONSTRAINT podcast_pkey PRIMARY KEY (podcast_id);


SET search_path = webcam, pg_catalog;

--
-- Name: memberviews_pkey; Type: CONSTRAINT; Schema: webcam
--

ALTER TABLE ONLY memberviews
    ADD CONSTRAINT memberviews_pkey PRIMARY KEY (memberid);


--
-- Name: streams_pkey; Type: CONSTRAINT; Schema: webcam
--

ALTER TABLE ONLY streams
    ADD CONSTRAINT streams_pkey PRIMARY KEY (streamid);


SET search_path = website, pg_catalog;

--
-- Name: banner_campaign_pkey; Type: CONSTRAINT; Schema: website
--

ALTER TABLE ONLY banner_campaign
    ADD CONSTRAINT banner_campaign_pkey PRIMARY KEY (banner_campaign_id);


--
-- Name: banner_location_pkey; Type: CONSTRAINT; Schema: website
--

ALTER TABLE ONLY banner_location
    ADD CONSTRAINT banner_location_pkey PRIMARY KEY (banner_location_id);


--
-- Name: banner_pkey; Type: CONSTRAINT; Schema: website
--

ALTER TABLE ONLY banner
    ADD CONSTRAINT banner_pkey PRIMARY KEY (banner_id);


--
-- Name: banner_timeslot_pkey; Type: CONSTRAINT; Schema: website
--

ALTER TABLE ONLY banner_timeslot
    ADD CONSTRAINT banner_timeslot_pkey PRIMARY KEY (id);


--
-- Name: banner_type_pkey; Type: CONSTRAINT; Schema: website
--

ALTER TABLE ONLY banner_type
    ADD CONSTRAINT banner_type_pkey PRIMARY KEY (banner_type_id);


SET search_path = jukebox, pg_catalog;

--
-- Name: playlist_availability_approvedid_idx; Type: INDEX; Schema: jukebox
--

CREATE INDEX playlist_availability_approvedid_idx ON playlist_availability USING btree (approvedid);


--
-- Name: playlist_availability_banner_id_idx; Type: INDEX; Schema: jukebox
--

CREATE INDEX playlist_availability_banner_id_idx ON playlist_availability USING btree (playlistid);


--
-- Name: playlist_availability_banner_location_id_idx; Type: INDEX; Schema: jukebox
--

CREATE INDEX playlist_availability_banner_location_id_idx ON playlist_availability USING btree (weight);


--
-- Name: playlist_availability_effective_from_idx; Type: INDEX; Schema: jukebox
--

CREATE INDEX playlist_availability_effective_from_idx ON playlist_availability USING btree (effective_from);


--
-- Name: playlist_availability_effective_to_idx; Type: INDEX; Schema: jukebox
--

CREATE INDEX playlist_availability_effective_to_idx ON playlist_availability USING btree (effective_to);


--
-- Name: playlist_availability_memberid_idx; Type: INDEX; Schema: jukebox
--

CREATE INDEX playlist_availability_memberid_idx ON playlist_availability USING btree (memberid);


SET search_path = metadata, pg_catalog;

--
-- Name: package_image_metadata_approvedid; Type: INDEX; Schema: metadata
--

CREATE INDEX package_image_metadata_approvedid ON package_image_metadata USING btree (approvedid);


--
-- Name: package_image_metadata_element_id; Type: INDEX; Schema: metadata
--

CREATE INDEX package_image_metadata_element_id ON package_image_metadata USING btree (element_id);


--
-- Name: package_image_metadata_memberid; Type: INDEX; Schema: metadata
--

CREATE INDEX package_image_metadata_memberid ON package_image_metadata USING btree (memberid);


--
-- Name: package_image_metadata_metadata_key_id; Type: INDEX; Schema: metadata
--

CREATE INDEX package_image_metadata_metadata_key_id ON package_image_metadata USING btree (metadata_key_id);


--
-- Name: package_name; Type: INDEX; Schema: metadata
--

CREATE INDEX package_name ON package USING btree (name);


--
-- Name: package_name_like; Type: INDEX; Schema: metadata
--

CREATE INDEX package_name_like ON package USING btree (name varchar_pattern_ops);


--
-- Name: package_text_metadata_approvedid; Type: INDEX; Schema: metadata
--

CREATE INDEX package_text_metadata_approvedid ON package_text_metadata USING btree (approvedid);


--
-- Name: package_text_metadata_element_id; Type: INDEX; Schema: metadata
--

CREATE INDEX package_text_metadata_element_id ON package_text_metadata USING btree (element_id);


--
-- Name: package_text_metadata_memberid; Type: INDEX; Schema: metadata
--

CREATE INDEX package_text_metadata_memberid ON package_text_metadata USING btree (memberid);


--
-- Name: package_text_metadata_metadata_key_id; Type: INDEX; Schema: metadata
--

CREATE INDEX package_text_metadata_metadata_key_id ON package_text_metadata USING btree (metadata_key_id);


SET search_path = music, pg_catalog;

--
-- Name: chart_release_chart_type_id; Type: INDEX; Schema: music
--

CREATE INDEX chart_release_chart_type_id ON chart_release USING btree (chart_type_id);


--
-- Name: chart_row_chart_release_id; Type: INDEX; Schema: music
--

CREATE INDEX chart_row_chart_release_id ON chart_row USING btree (chart_release_id);


--
-- Name: chart_type_name; Type: INDEX; Schema: music
--

CREATE INDEX chart_type_name ON chart_type USING btree (name);


--
-- Name: chart_type_name_like; Type: INDEX; Schema: music
--

CREATE INDEX chart_type_name_like ON chart_type USING btree (name varchar_pattern_ops);


SET search_path = people, pg_catalog;

--
-- Name: group_root_role_group_type_id; Type: INDEX; Schema: people
--

CREATE INDEX group_root_role_group_type_id ON group_root_role USING btree (group_type_id);


--
-- Name: role_metadata_effective_from_index; Type: INDEX; Schema: people
--

CREATE INDEX role_metadata_effective_from_index ON role_text_metadata USING btree (effective_from);


--
-- Name: role_metadata_effective_to_index; Type: INDEX; Schema: people
--

CREATE INDEX role_metadata_effective_to_index ON role_text_metadata USING btree (effective_to);


SET search_path = public, pg_catalog;

--
-- Name: audiolog_timeplayed_index; Type: INDEX; Schema: public
--

CREATE INDEX audiolog_timeplayed_index ON baps_audiolog USING btree (timeplayed);


--
-- Name: audiolog_timestopped_index; Type: INDEX; Schema: public
--

CREATE INDEX audiolog_timestopped_index ON baps_audiolog USING btree (timestopped);


--
-- Name: baps_item_fileitemid_key; Type: INDEX; Schema: public
--

CREATE INDEX baps_item_fileitemid_key ON baps_item USING btree (fileitemid);


--
-- Name: baps_item_libraryitemid_key; Type: INDEX; Schema: public
--

CREATE INDEX baps_item_libraryitemid_key ON baps_item USING btree (libraryitemid);


--
-- Name: baps_item_listingid_key; Type: INDEX; Schema: public
--

CREATE INDEX baps_item_listingid_key ON baps_item USING btree (listingid);


--
-- Name: baps_item_position_key; Type: INDEX; Schema: public
--

CREATE INDEX baps_item_position_key ON baps_item USING btree ("position");


--
-- Name: baps_item_textitemid_key; Type: INDEX; Schema: public
--

CREATE INDEX baps_item_textitemid_key ON baps_item USING btree (textitemid);


--
-- Name: baps_item_viewable_key; Type: INDEX; Schema: public
--

CREATE INDEX baps_item_viewable_key ON baps_show USING btree (viewable);


--
-- Name: baps_libraryitem_trackid_index; Type: INDEX; Schema: public
--

CREATE INDEX baps_libraryitem_trackid_index ON baps_libraryitem USING btree (trackid);


--
-- Name: baps_listing_channel_key; Type: INDEX; Schema: public
--

CREATE INDEX baps_listing_channel_key ON baps_listing USING btree (channel);


--
-- Name: baps_listing_showid_key; Type: INDEX; Schema: public
--

CREATE INDEX baps_listing_showid_key ON baps_listing USING btree (showid);


--
-- Name: baps_show_broadcastdate_index; Type: INDEX; Schema: public
--

CREATE INDEX baps_show_broadcastdate_index ON baps_show USING btree (broadcastdate);


--
-- Name: baps_show_userid_key; Type: INDEX; Schema: public
--

CREATE INDEX baps_show_userid_key ON baps_show USING btree (userid);


--
-- Name: baps_user_usernamechart_key; Type: INDEX; Schema: public
--

CREATE INDEX baps_user_usernamechart_key ON baps_user USING btree (username) WHERE ((username)::text = 'chart'::text);


--
-- Name: chartweektimestamp; Type: INDEX; Schema: public
--

CREATE INDEX chartweektimestamp ON chart USING btree (chartweek);


--
-- Name: i_endtime; Type: INDEX; Schema: public
--

CREATE INDEX i_endtime ON strm_log USING btree (endtime);


--
-- Name: idx_member_eduroam; Type: INDEX; Schema: public
--

CREATE INDEX idx_member_eduroam ON member USING btree (eduroam);


--
-- Name: idx_member_email; Type: INDEX; Schema: public
--

CREATE INDEX idx_member_email ON member USING btree (email);


--
-- Name: idx_member_localalias; Type: INDEX; Schema: public
--

CREATE INDEX idx_member_localalias ON member USING btree (local_alias);


--
-- Name: idx_member_localname; Type: INDEX; Schema: public
--

CREATE INDEX idx_member_localname ON member USING btree (local_name);


--
-- Name: l_college_collegeid_key; Type: INDEX; Schema: public
--

CREATE UNIQUE INDEX l_college_collegeid_key ON l_college USING btree (collegeid);


--
-- Name: l_musicinterest_typeid_key; Type: INDEX; Schema: public
--

CREATE UNIQUE INDEX l_musicinterest_typeid_key ON l_musicinterest USING btree (typeid);


--
-- Name: member_memberid_key; Type: INDEX; Schema: public
--

CREATE UNIQUE INDEX member_memberid_key ON member USING btree (memberid);


--
-- Name: member_office_member_office_key; Type: INDEX; Schema: public
--

CREATE UNIQUE INDEX member_office_member_office_key ON member_officer USING btree (member_officerid);


--
-- Name: officer_officerid_key; Type: INDEX; Schema: public
--

CREATE UNIQUE INDEX officer_officerid_key ON officer USING btree (officerid);


--
-- Name: rec_record_dateadded_key; Type: INDEX; Schema: public
--

CREATE INDEX rec_record_dateadded_key ON rec_record USING btree (dateadded);


--
-- Name: rec_record_format_key; Type: INDEX; Schema: public
--

CREATE INDEX rec_record_format_key ON rec_record USING btree (format) WHERE (format = 's'::bpchar);


--
-- Name: rec_record_recordid_key; Type: INDEX; Schema: public
--

CREATE UNIQUE INDEX rec_record_recordid_key ON rec_record USING btree (recordid);


--
-- Name: rec_track_artist_index; Type: INDEX; Schema: public
--

CREATE INDEX rec_track_artist_index ON rec_track USING btree (artist);


--
-- Name: rec_track_recordid_key; Type: INDEX; Schema: public
--

CREATE INDEX rec_track_recordid_key ON rec_track USING btree (recordid);


--
-- Name: rec_track_trackid_key; Type: INDEX; Schema: public
--

CREATE UNIQUE INDEX rec_track_trackid_key ON rec_track USING btree (trackid);


--
-- Name: rec_unique_recordid; Type: INDEX; Schema: public
--

CREATE INDEX rec_unique_recordid ON rec_labelqueue USING btree (recordid);


--
-- Name: recommended_listening_chartweek_key; Type: INDEX; Schema: public
--

CREATE INDEX recommended_listening_chartweek_key ON recommended_listening USING btree (week);

--
-- Name: strm_log_starttime_key; Type: INDEX; Schema: public
--

CREATE INDEX strm_log_starttime_key ON strm_log USING btree (starttime);

ALTER TABLE strm_log CLUSTER ON strm_log_starttime_key;


--
-- Name: strm_log_steamid_key; Type: INDEX; Schema: public
--

CREATE INDEX strm_log_steamid_key ON strm_log USING btree (streamid);


--
-- Name: team_teamid_key; Type: INDEX; Schema: public
--

CREATE UNIQUE INDEX team_teamid_key ON team USING btree (teamid);


SET search_path = schedule, pg_catalog;

--
-- Name: block_range_rule_end_time_index; Type: INDEX; Schema: schedule
--

CREATE INDEX block_range_rule_end_time_index ON block_range_rule USING btree (end_time);


--
-- Name: block_range_rule_start_time_index; Type: INDEX; Schema: schedule
--

CREATE INDEX block_range_rule_start_time_index ON block_range_rule USING btree (start_time);


--
-- Name: duration; Type: INDEX; Schema: schedule
--

CREATE INDEX duration ON show_season_timeslot USING btree (duration);


--
-- Name: season_metadata_effective_from_index; Type: INDEX; Schema: schedule
--

CREATE INDEX season_metadata_effective_from_index ON season_metadata USING btree (effective_from);


--
-- Name: season_metadata_effective_to_index; Type: INDEX; Schema: schedule
--

CREATE INDEX season_metadata_effective_to_index ON season_metadata USING btree (effective_to);


--
-- Name: show_credit_effective_from_index; Type: INDEX; Schema: schedule
--

CREATE INDEX show_credit_effective_from_index ON show_credit USING btree (effective_from);


--
-- Name: show_credit_effective_to_index; Type: INDEX; Schema: schedule
--

CREATE INDEX show_credit_effective_to_index ON show_credit USING btree (effective_to);


--
-- Name: show_id_index; Type: INDEX; Schema: schedule
--

CREATE INDEX show_id_index ON show_credit USING btree (show_id);


--
-- Name: show_image_metadata_approvedid; Type: INDEX; Schema: schedule
--

CREATE INDEX show_image_metadata_approvedid ON show_image_metadata USING btree (approvedid);


--
-- Name: show_image_metadata_memberid; Type: INDEX; Schema: schedule
--

CREATE INDEX show_image_metadata_memberid ON show_image_metadata USING btree (memberid);


--
-- Name: show_image_metadata_metadata_key_id; Type: INDEX; Schema: schedule
--

CREATE INDEX show_image_metadata_metadata_key_id ON show_image_metadata USING btree (metadata_key_id);


--
-- Name: show_image_metadata_show_id; Type: INDEX; Schema: schedule
--

CREATE INDEX show_image_metadata_show_id ON show_image_metadata USING btree (show_id);


--
-- Name: show_metadata_effective_from_index; Type: INDEX; Schema: schedule
--

CREATE INDEX show_metadata_effective_from_index ON show_metadata USING btree (effective_from);


--
-- Name: show_metadata_effective_to_index; Type: INDEX; Schema: schedule
--

CREATE INDEX show_metadata_effective_to_index ON show_metadata USING btree (effective_to);


--
-- Name: show_podcast_link_podcast_id; Type: INDEX; Schema: schedule
--

CREATE INDEX show_podcast_link_podcast_id ON show_podcast_link USING btree (podcast_id);


--
-- Name: show_season_index; Type: INDEX; Schema: schedule
--

CREATE INDEX show_season_index ON show_season_timeslot USING btree (show_season_id);


--
-- Name: start_time_index; Type: INDEX; Schema: schedule
--

CREATE INDEX start_time_index ON show_season_timeslot USING btree (start_time);


--
-- Name: timeslot_metadata_effective_from_index; Type: INDEX; Schema: schedule
--

CREATE INDEX timeslot_metadata_effective_from_index ON timeslot_metadata USING btree (effective_from);


--
-- Name: timeslot_metadata_effective_to_index; Type: INDEX; Schema: schedule
--

CREATE INDEX timeslot_metadata_effective_to_index ON timeslot_metadata USING btree (effective_to);


SET search_path = tracklist, pg_catalog;

--
-- Name: index_tracklist_tracklist_timeslotid; Type: INDEX; Schema: tracklist
--

CREATE INDEX index_tracklist_tracklist_timeslotid ON tracklist USING btree (timeslotid);


--
-- Name: index_tracklist_tracklist_timestart; Type: INDEX; Schema: tracklist
--

CREATE INDEX index_tracklist_tracklist_timestart ON tracklist USING btree (timestart);


--
-- Name: index_tracklist_tracklist_timestop; Type: INDEX; Schema: tracklist
--

CREATE INDEX index_tracklist_tracklist_timestop ON tracklist USING btree (timestop);


--
-- Name: tracklist_tracklist_timeslotid; Type: INDEX; Schema: tracklist
--

CREATE INDEX tracklist_tracklist_timeslotid ON tracklist USING btree (timeslotid);


SET search_path = uryplayer, pg_catalog;

--
-- Name: podcast_approvedid; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_approvedid ON podcast USING btree (approvedid);


--
-- Name: podcast_credit_approvedid; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_credit_approvedid ON podcast_credit USING btree (approvedid);


--
-- Name: podcast_credit_credit_type_id; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_credit_credit_type_id ON podcast_credit USING btree (credit_type_id);


--
-- Name: podcast_credit_creditid; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_credit_creditid ON podcast_credit USING btree (creditid);


--
-- Name: podcast_credit_memberid; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_credit_memberid ON podcast_credit USING btree (memberid);


--
-- Name: podcast_credit_podcast_id; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_credit_podcast_id ON podcast_credit USING btree (podcast_id);


--
-- Name: podcast_image_metadata_approvedid; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_image_metadata_approvedid ON podcast_image_metadata USING btree (approvedid);


--
-- Name: podcast_image_metadata_memberid; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_image_metadata_memberid ON podcast_image_metadata USING btree (memberid);


--
-- Name: podcast_image_metadata_metadata_key_id; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_image_metadata_metadata_key_id ON podcast_image_metadata USING btree (metadata_key_id);


--
-- Name: podcast_image_metadata_podcast_id; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_image_metadata_podcast_id ON podcast_image_metadata USING btree (podcast_id);


--
-- Name: podcast_memberid; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_memberid ON podcast USING btree (memberid);


--
-- Name: podcast_metadata_approvedid; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_metadata_approvedid ON podcast_metadata USING btree (approvedid);


--
-- Name: podcast_metadata_memberid; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_metadata_memberid ON podcast_metadata USING btree (memberid);


--
-- Name: podcast_metadata_metadata_key_id; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_metadata_metadata_key_id ON podcast_metadata USING btree (metadata_key_id);


--
-- Name: podcast_metadata_podcast_id; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_metadata_podcast_id ON podcast_metadata USING btree (podcast_id);


--
-- Name: podcast_package_entry_approvedid; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_package_entry_approvedid ON podcast_package_entry USING btree (approvedid);


--
-- Name: podcast_package_entry_memberid; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_package_entry_memberid ON podcast_package_entry USING btree (memberid);


--
-- Name: podcast_package_entry_package_id; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_package_entry_package_id ON podcast_package_entry USING btree (package_id);


--
-- Name: podcast_package_entry_podcast_id; Type: INDEX; Schema: uryplayer
--

CREATE INDEX podcast_package_entry_podcast_id ON podcast_package_entry USING btree (podcast_id);


SET search_path = website, pg_catalog;

--
-- Name: banner_campaign_approvedid; Type: INDEX; Schema: website
--

CREATE INDEX banner_campaign_approvedid ON banner_campaign USING btree (approvedid);


--
-- Name: banner_campaign_banner_id; Type: INDEX; Schema: website
--

CREATE INDEX banner_campaign_banner_id ON banner_campaign USING btree (banner_id);


--
-- Name: banner_campaign_banner_location_id; Type: INDEX; Schema: website
--

CREATE INDEX banner_campaign_banner_location_id ON banner_campaign USING btree (banner_location_id);


--
-- Name: banner_campaign_effective_from_index; Type: INDEX; Schema: website
--

CREATE INDEX banner_campaign_effective_from_index ON banner_campaign USING btree (effective_from);


--
-- Name: banner_campaign_effective_to_index; Type: INDEX; Schema: website
--

CREATE INDEX banner_campaign_effective_to_index ON banner_campaign USING btree (effective_to);


--
-- Name: banner_campaign_memberid; Type: INDEX; Schema: website
--

CREATE INDEX banner_campaign_memberid ON banner_campaign USING btree (memberid);


--
-- Name: banner_location_name; Type: INDEX; Schema: website
--

CREATE INDEX banner_location_name ON banner_location USING btree (name);


--
-- Name: banner_location_name_like; Type: INDEX; Schema: website
--

CREATE INDEX banner_location_name_like ON banner_location USING btree (name varchar_pattern_ops);


--
-- Name: banner_timeslot_approvedid; Type: INDEX; Schema: website
--

CREATE INDEX banner_timeslot_approvedid ON banner_timeslot USING btree (approvedid);


--
-- Name: banner_timeslot_banner_campaign_id; Type: INDEX; Schema: website
--

CREATE INDEX banner_timeslot_banner_campaign_id ON banner_timeslot USING btree (banner_campaign_id);


--
-- Name: banner_timeslot_from_time_index; Type: INDEX; Schema: website
--

CREATE INDEX banner_timeslot_from_time_index ON banner_timeslot USING btree (start_time);


--
-- Name: banner_timeslot_memberid; Type: INDEX; Schema: website
--

CREATE INDEX banner_timeslot_memberid ON banner_timeslot USING btree (memberid);


--
-- Name: banner_timeslot_to_time_index; Type: INDEX; Schema: website
--

CREATE INDEX banner_timeslot_to_time_index ON banner_timeslot USING btree (end_time);


--
-- Name: banner_type_name; Type: INDEX; Schema: website
--

CREATE INDEX banner_type_name ON banner_type USING btree (name);


--
-- Name: banner_type_name_like; Type: INDEX; Schema: website
--

CREATE INDEX banner_type_name_like ON banner_type USING btree (name varchar_pattern_ops);


SET search_path = public, pg_catalog;

--
-- Name: bapstotracklist; Type: TRIGGER; Schema: public
--

CREATE TRIGGER bapstotracklist AFTER UPDATE ON baps_audiolog FOR EACH ROW EXECUTE PROCEDURE bapstotracklist();


--
-- Name: clearitem; Type: TRIGGER; Schema: public
--

CREATE TRIGGER clearitem BEFORE DELETE ON baps_item FOR EACH ROW EXECUTE PROCEDURE clear_item_func();

ALTER TABLE baps_item DISABLE TRIGGER clearitem;


--
-- Name: set_shelfcode_trigger; Type: TRIGGER; Schema: public
--

CREATE TRIGGER set_shelfcode_trigger BEFORE INSERT ON rec_record FOR EACH ROW EXECUTE PROCEDURE set_shelfcode_func();


SET search_path = bapsplanner, pg_catalog;

--
-- Name: managed_items_managedplaylistid_fkey; Type: FK CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY managed_items
    ADD CONSTRAINT managed_items_managedplaylistid_fkey FOREIGN KEY (managedplaylistid) REFERENCES managed_playlists(managedplaylistid) ON DELETE CASCADE;


--
-- Name: managed_items_memberid_fkey; Type: FK CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY managed_items
    ADD CONSTRAINT managed_items_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) ON DELETE SET NULL;


--
-- Name: secure_play_token_trackid_fkey; Type: FK CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY secure_play_token
    ADD CONSTRAINT secure_play_token_trackid_fkey FOREIGN KEY (trackid) REFERENCES public.rec_track(trackid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: spt_fk_memberid; Type: FK CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY secure_play_token
    ADD CONSTRAINT spt_fk_memberid FOREIGN KEY (memberid) REFERENCES public.member(memberid);


--
-- Name: timeslot_change_ops_client_id_fkey; Type: FK CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY timeslot_change_ops
    ADD CONSTRAINT timeslot_change_ops_client_id_fkey FOREIGN KEY (client_id) REFERENCES client_ids(client_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: timeslot_items_rec_track_id_fkey; Type: FK CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY timeslot_items
    ADD CONSTRAINT timeslot_items_rec_track_id_fkey FOREIGN KEY (rec_track_id) REFERENCES public.rec_track(trackid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: timeslot_items_timeslot_id_fkey; Type: FK CONSTRAINT; Schema: bapsplanner
--

ALTER TABLE ONLY timeslot_items
    ADD CONSTRAINT timeslot_items_timeslot_id_fkey FOREIGN KEY (timeslot_id) REFERENCES schedule.show_season_timeslot(show_season_timeslot_id) ON UPDATE CASCADE ON DELETE CASCADE;


SET search_path = jukebox, pg_catalog;

--
-- Name: jukebox_playlist_lock; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlists
    ADD CONSTRAINT jukebox_playlist_lock FOREIGN KEY (lock) REFERENCES public.member(memberid);


--
-- Name: playlist_availability_pkey; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlist_availability
    ADD CONSTRAINT playlist_availability_pkey PRIMARY KEY (playlist_availability_id);


--
-- Name: playlist_availability_playlistid_fkey; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlist_availability
    ADD CONSTRAINT playlist_availability_playlistid_fkey FOREIGN KEY (playlistid) REFERENCES playlists(playlistid) ON UPDATE CASCADE;


--
-- Name: playlist_entries_playlistid_fkey; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlist_entries
    ADD CONSTRAINT playlist_entries_playlistid_fkey FOREIGN KEY (playlistid) REFERENCES playlists(playlistid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: playlist_entries_playlistid_fkey1; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlist_entries
    ADD CONSTRAINT playlist_entries_playlistid_fkey1 FOREIGN KEY (playlistid, revision_added) REFERENCES playlist_revisions(playlistid, revisionid) ON DELETE CASCADE;


--
-- Name: playlist_entries_playlistid_fkey2; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlist_entries
    ADD CONSTRAINT playlist_entries_playlistid_fkey2 FOREIGN KEY (playlistid, revision_removed) REFERENCES playlist_revisions(playlistid, revisionid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: playlist_entries_trackid_fkey; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlist_entries
    ADD CONSTRAINT playlist_entries_trackid_fkey FOREIGN KEY (trackid) REFERENCES public.rec_track(trackid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: playlist_revisions_author_fkey; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlist_revisions
    ADD CONSTRAINT playlist_revisions_author_fkey FOREIGN KEY (author) REFERENCES public.member(memberid);


--
-- Name: playlist_revisions_playlistid_fkey; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlist_revisions
    ADD CONSTRAINT playlist_revisions_playlistid_fkey FOREIGN KEY (playlistid) REFERENCES playlists(playlistid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: playlist_timeslot_approvedid_fkey; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlist_timeslot
    ADD CONSTRAINT playlist_timeslot_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid);


--
-- Name: playlist_timeslot_memberid_fkey; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlist_timeslot
    ADD CONSTRAINT playlist_timeslot_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid);


--
-- Name: playlist_timeslot_playlist_availability_id_fkey; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY playlist_timeslot
    ADD CONSTRAINT playlist_timeslot_playlist_availability_id_fkey FOREIGN KEY (playlist_availability_id) REFERENCES playlist_availability(playlist_availability_id);


--
-- Name: request_memberid_fkey; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY request
    ADD CONSTRAINT request_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid);


--
-- Name: request_trackid_fkey; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY request
    ADD CONSTRAINT request_trackid_fkey FOREIGN KEY (trackid) REFERENCES public.rec_track(trackid);


--
-- Name: silence_log_handledby_fkey; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY silence_log
    ADD CONSTRAINT silence_log_handledby_fkey FOREIGN KEY (handledby) REFERENCES public.member(memberid) ON DELETE SET NULL;


--
-- Name: track_blacklist_trackid_fkey; Type: FK CONSTRAINT; Schema: jukebox
--

ALTER TABLE ONLY track_blacklist
    ADD CONSTRAINT track_blacklist_trackid_fkey FOREIGN KEY (trackid) REFERENCES public.rec_track(trackid);


SET search_path = mail, pg_catalog;

--
-- Name: alias_list_alias_id_fkey; Type: FK CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY alias_list
    ADD CONSTRAINT alias_list_alias_id_fkey FOREIGN KEY (alias_id) REFERENCES alias(alias_id);


--
-- Name: alias_list_destination_fkey; Type: FK CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY alias_list
    ADD CONSTRAINT alias_list_destination_fkey FOREIGN KEY (destination) REFERENCES public.mail_list(listid);


--
-- Name: alias_member_alias_id_fkey; Type: FK CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY alias_member
    ADD CONSTRAINT alias_member_alias_id_fkey FOREIGN KEY (alias_id) REFERENCES alias(alias_id);


--
-- Name: alias_member_destination_fkey; Type: FK CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY alias_member
    ADD CONSTRAINT alias_member_destination_fkey FOREIGN KEY (destination) REFERENCES public.member(memberid);


--
-- Name: alias_officer_alias_id_fkey; Type: FK CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY alias_officer
    ADD CONSTRAINT alias_officer_alias_id_fkey FOREIGN KEY (alias_id) REFERENCES alias(alias_id);


--
-- Name: alias_officer_destination_fkey; Type: FK CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY alias_officer
    ADD CONSTRAINT alias_officer_destination_fkey FOREIGN KEY (destination) REFERENCES public.officer(officerid);


--
-- Name: alias_text_alias_id_fkey; Type: FK CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY alias_text
    ADD CONSTRAINT alias_text_alias_id_fkey FOREIGN KEY (alias_id) REFERENCES alias(alias_id);


--
-- Name: email_recipient_list_email_id_fkey; Type: FK CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY email_recipient_list
    ADD CONSTRAINT email_recipient_list_email_id_fkey FOREIGN KEY (email_id) REFERENCES email(email_id) ON DELETE CASCADE;


--
-- Name: email_recipient_list_listid_fkey; Type: FK CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY email_recipient_list
    ADD CONSTRAINT email_recipient_list_listid_fkey FOREIGN KEY (listid) REFERENCES public.mail_list(listid) ON DELETE RESTRICT;


--
-- Name: email_recipient_user_email_id_fkey; Type: FK CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY email_recipient_member
    ADD CONSTRAINT email_recipient_user_email_id_fkey FOREIGN KEY (email_id) REFERENCES email(email_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: email_recipient_user_memberid_fkey; Type: FK CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY email_recipient_member
    ADD CONSTRAINT email_recipient_user_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) ON DELETE RESTRICT;


--
-- Name: email_sender_fkey; Type: FK CONSTRAINT; Schema: mail
--

ALTER TABLE ONLY email
    ADD CONSTRAINT email_sender_fkey FOREIGN KEY (sender) REFERENCES public.member(memberid) ON DELETE RESTRICT;


SET search_path = metadata, pg_catalog;

--
-- Name: package_image_metadata_approvedid_fkey; Type: FK CONSTRAINT; Schema: metadata
--

ALTER TABLE ONLY package_image_metadata
    ADD CONSTRAINT package_image_metadata_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: package_image_metadata_element_id_fkey; Type: FK CONSTRAINT; Schema: metadata
--

ALTER TABLE ONLY package_image_metadata
    ADD CONSTRAINT package_image_metadata_element_id_fkey FOREIGN KEY (element_id) REFERENCES package(package_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: package_image_metadata_memberid_fkey; Type: FK CONSTRAINT; Schema: metadata
--

ALTER TABLE ONLY package_image_metadata
    ADD CONSTRAINT package_image_metadata_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: package_image_metadata_metadata_key_id_fkey; Type: FK CONSTRAINT; Schema: metadata
--

ALTER TABLE ONLY package_image_metadata
    ADD CONSTRAINT package_image_metadata_metadata_key_id_fkey FOREIGN KEY (metadata_key_id) REFERENCES metadata_key(metadata_key_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: package_text_metadata_approvedid_fkey; Type: FK CONSTRAINT; Schema: metadata
--

ALTER TABLE ONLY package_text_metadata
    ADD CONSTRAINT package_text_metadata_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: package_text_metadata_element_id_fkey; Type: FK CONSTRAINT; Schema: metadata
--

ALTER TABLE ONLY package_text_metadata
    ADD CONSTRAINT package_text_metadata_element_id_fkey FOREIGN KEY (element_id) REFERENCES package(package_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: package_text_metadata_memberid_fkey; Type: FK CONSTRAINT; Schema: metadata
--

ALTER TABLE ONLY package_text_metadata
    ADD CONSTRAINT package_text_metadata_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: package_text_metadata_metadata_key_id_fkey; Type: FK CONSTRAINT; Schema: metadata
--

ALTER TABLE ONLY package_text_metadata
    ADD CONSTRAINT package_text_metadata_metadata_key_id_fkey FOREIGN KEY (metadata_key_id) REFERENCES metadata_key(metadata_key_id) DEFERRABLE INITIALLY DEFERRED;


SET search_path = music, pg_catalog;

--
-- Name: chart_release_chart_type_id_fkey; Type: FK CONSTRAINT; Schema: music
--

ALTER TABLE ONLY chart_release
    ADD CONSTRAINT chart_release_chart_type_id_fkey FOREIGN KEY (chart_type_id) REFERENCES chart_type(chart_type_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: chart_row_chart_release_id_fkey; Type: FK CONSTRAINT; Schema: music
--

ALTER TABLE ONLY chart_row
    ADD CONSTRAINT chart_row_chart_release_id_fkey FOREIGN KEY (chart_release_id) REFERENCES chart_release(chart_release_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: chart_row_trackid_fkey; Type: FK CONSTRAINT; Schema: music
--

ALTER TABLE ONLY chart_row
    ADD CONSTRAINT chart_row_trackid_fkey FOREIGN KEY (trackid) REFERENCES public.rec_track(trackid);


SET search_path = myury, pg_catalog;

--
-- Name: act_permission_actionid_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY act_permission
    ADD CONSTRAINT act_permission_actionid_fkey FOREIGN KEY (actionid) REFERENCES actions(actionid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: act_permission_moduleid_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY act_permission
    ADD CONSTRAINT act_permission_moduleid_fkey FOREIGN KEY (moduleid) REFERENCES modules(moduleid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: act_permission_serviceid_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY act_permission
    ADD CONSTRAINT act_permission_serviceid_fkey FOREIGN KEY (serviceid) REFERENCES services(serviceid) ON DELETE CASCADE;


--
-- Name: act_permission_typeid_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY act_permission
    ADD CONSTRAINT act_permission_typeid_fkey FOREIGN KEY (typeid) REFERENCES public.l_action(typeid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: actions_moduleid_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY actions
    ADD CONSTRAINT actions_moduleid_fkey FOREIGN KEY (moduleid) REFERENCES modules(moduleid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: api_key_auth_api_key_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY api_key_auth
    ADD CONSTRAINT api_key_auth_api_key_fkey FOREIGN KEY (key_string) REFERENCES api_key(key_string);


--
-- Name: api_key_auth_auth_id_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY api_key_auth
    ADD CONSTRAINT api_key_auth_auth_id_fkey FOREIGN KEY (typeid) REFERENCES public.l_action(typeid);


--
-- Name: api_method_auth_typeid_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY api_method_auth
    ADD CONSTRAINT api_method_auth_typeid_fkey FOREIGN KEY (typeid) REFERENCES public.l_action(typeid);


--
-- Name: award_member_awardedby_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY award_member
    ADD CONSTRAINT award_member_awardedby_fkey FOREIGN KEY (awardedby) REFERENCES public.member(memberid) ON DELETE RESTRICT;


--
-- Name: award_member_awardid_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY award_member
    ADD CONSTRAINT award_member_awardid_fkey FOREIGN KEY (awardid) REFERENCES award_categories(awardid) ON DELETE RESTRICT;


--
-- Name: award_member_memberid_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY award_member
    ADD CONSTRAINT award_member_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) ON DELETE RESTRICT;


--
-- Name: modules_serviceid_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY modules
    ADD CONSTRAINT modules_serviceid_fkey FOREIGN KEY (serviceid) REFERENCES services(serviceid) ON DELETE CASCADE;


--
-- Name: password_reset_token_memberid_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY password_reset_token
    ADD CONSTRAINT password_reset_token_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid);


--
-- Name: services_versions_member_memberid_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY services_versions_member
    ADD CONSTRAINT services_versions_member_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid);


--
-- Name: services_versions_member_serviceversionid_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY services_versions_member
    ADD CONSTRAINT services_versions_member_serviceversionid_fkey FOREIGN KEY (serviceversionid) REFERENCES services_versions(serviceversionid);


--
-- Name: services_versions_serviceid_fkey; Type: FK CONSTRAINT; Schema: myury
--

ALTER TABLE ONLY services_versions
    ADD CONSTRAINT services_versions_serviceid_fkey FOREIGN KEY (serviceid) REFERENCES services(serviceid) ON UPDATE CASCADE ON DELETE CASCADE;


SET search_path = people, pg_catalog;

--
-- Name: group_root_role_group_leader_id_fkey; Type: FK CONSTRAINT; Schema: people
--

ALTER TABLE ONLY group_root_role
    ADD CONSTRAINT group_root_role_group_leader_id_fkey FOREIGN KEY (group_leader_id) REFERENCES role(role_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: group_root_role_group_type_id_fkey; Type: FK CONSTRAINT; Schema: people
--

ALTER TABLE ONLY group_root_role
    ADD CONSTRAINT group_root_role_group_type_id_fkey FOREIGN KEY (group_type_id) REFERENCES group_type(group_type_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: group_root_role_role_id_id_fkey; Type: FK CONSTRAINT; Schema: people
--

ALTER TABLE ONLY group_root_role
    ADD CONSTRAINT group_root_role_role_id_id_fkey FOREIGN KEY (role_id_id) REFERENCES role(role_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: metadata_roleid_fkey; Type: FK CONSTRAINT; Schema: people
--

ALTER TABLE ONLY metadata
    ADD CONSTRAINT metadata_roleid_fkey FOREIGN KEY (roleid) REFERENCES role(role_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: parents_childid_fkey; Type: FK CONSTRAINT; Schema: people
--

ALTER TABLE ONLY role_inheritance
    ADD CONSTRAINT parents_childid_fkey FOREIGN KEY (child_id) REFERENCES role(role_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: parents_parentid_fkey; Type: FK CONSTRAINT; Schema: people
--

ALTER TABLE ONLY role_inheritance
    ADD CONSTRAINT parents_parentid_fkey FOREIGN KEY (parent_id) REFERENCES role(role_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: quote_source_fkey; Type: FK CONSTRAINT; Schema: people
--

ALTER TABLE ONLY quote
    ADD CONSTRAINT quote_source_fkey FOREIGN KEY (source) REFERENCES public.member(memberid);


--
-- Name: role_metadata_approvedid_fkey; Type: FK CONSTRAINT; Schema: people
--

ALTER TABLE ONLY role_text_metadata
    ADD CONSTRAINT role_metadata_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: role_metadata_memberid_fkey; Type: FK CONSTRAINT; Schema: people
--

ALTER TABLE ONLY role_text_metadata
    ADD CONSTRAINT role_metadata_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: role_metadata_metadata_key_id_fkey; Type: FK CONSTRAINT; Schema: people
--

ALTER TABLE ONLY role_text_metadata
    ADD CONSTRAINT role_metadata_metadata_key_id_fkey FOREIGN KEY (metadata_key_id) REFERENCES metadata.metadata_key(metadata_key_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: role_metadata_role_id_fkey; Type: FK CONSTRAINT; Schema: people
--

ALTER TABLE ONLY role_text_metadata
    ADD CONSTRAINT role_metadata_role_id_fkey FOREIGN KEY (role_id) REFERENCES role(role_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: roles_visibilitylevel_fkey; Type: FK CONSTRAINT; Schema: people
--

ALTER TABLE ONLY role
    ADD CONSTRAINT roles_visibilitylevel_fkey FOREIGN KEY (visibilitylevel) REFERENCES role_visibility(role_visibility_id) ON UPDATE CASCADE ON DELETE RESTRICT;


SET search_path = public, pg_catalog;

--
-- Name: $1; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_officer
    ADD CONSTRAINT "$1" FOREIGN KEY (memberid) REFERENCES member(memberid) ON DELETE RESTRICT;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY team
    ADD CONSTRAINT "$1" FOREIGN KEY (status) REFERENCES l_status(statusid) ON DELETE RESTRICT;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY officer
    ADD CONSTRAINT "$1" FOREIGN KEY (teamid) REFERENCES team(teamid) ON DELETE RESTRICT;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_presenterstatus
    ADD CONSTRAINT "$1" FOREIGN KEY (confirmedby) REFERENCES member(memberid) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_track
    ADD CONSTRAINT "$1" FOREIGN KEY (recordid) REFERENCES rec_record(recordid) ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_record
    ADD CONSTRAINT "$1" FOREIGN KEY (status) REFERENCES rec_statuslookup(status_code) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_labelqueue
    ADD CONSTRAINT "$1" FOREIGN KEY (recordid) REFERENCES rec_record(recordid) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_user_external
    ADD CONSTRAINT "$1" FOREIGN KEY (userid) REFERENCES baps_user(userid) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_user_filefolder
    ADD CONSTRAINT "$1" FOREIGN KEY (userid) REFERENCES baps_user(userid) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_filefolder
    ADD CONSTRAINT "$1" FOREIGN KEY (owner) REFERENCES baps_user(userid) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_audiolog
    ADD CONSTRAINT "$1" FOREIGN KEY (serverid) REFERENCES baps_server(serverid) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_item
    ADD CONSTRAINT "$1" FOREIGN KEY (textitemid) REFERENCES baps_textitem(textitemid) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_libraryitem
    ADD CONSTRAINT "$1" FOREIGN KEY (recordid) REFERENCES rec_record(recordid) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member
    ADD CONSTRAINT "$2" FOREIGN KEY (college) REFERENCES l_college(collegeid) ON DELETE RESTRICT;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY officer
    ADD CONSTRAINT "$2" FOREIGN KEY (status) REFERENCES l_status(statusid) ON DELETE RESTRICT;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_officer
    ADD CONSTRAINT "$2" FOREIGN KEY (officerid) REFERENCES officer(officerid) ON DELETE RESTRICT;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_presenterstatus
    ADD CONSTRAINT "$2" FOREIGN KEY (memberid) REFERENCES member(memberid) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY strm_log
    ADD CONSTRAINT "$2" FOREIGN KEY (streamid) REFERENCES strm_stream(streamid) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_track
    ADD CONSTRAINT "$2" FOREIGN KEY (genre) REFERENCES rec_genrelookup(genre_code) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_record
    ADD CONSTRAINT "$2" FOREIGN KEY (media) REFERENCES rec_medialookup(media_code) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_user_filefolder
    ADD CONSTRAINT "$2" FOREIGN KEY (filefolderid) REFERENCES baps_filefolder(filefolderid) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_audiolog
    ADD CONSTRAINT "$2" FOREIGN KEY (audioid) REFERENCES baps_audio(audioid) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_libraryitem
    ADD CONSTRAINT "$2" FOREIGN KEY (trackid) REFERENCES rec_track(trackid) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_user_external
    ADD CONSTRAINT "$2" FOREIGN KEY (externalid) REFERENCES member(memberid) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- Name: $3; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY strm_log
    ADD CONSTRAINT "$3" FOREIGN KEY (useragentid) REFERENCES strm_useragent(useragentid) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: $3; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_track
    ADD CONSTRAINT "$3" FOREIGN KEY (clean) REFERENCES rec_cleanlookup(clean_code) ON UPDATE RESTRICT ON DELETE RESTRICT;

--
-- Name: rec_track_lasteditedby_fkey Type: FK CONSTRAINT; Schema: public; Owner: myradio
--

ALTER TABLE ONLY rec_track
    ADD CONSTRAINT rec_track_lasteditedby_fkey FOREIGN KEY (last_edited_memberid) REFERENCES member(memberid) ON UPDATE CASCADE ON DELETE SET NULL;

--
-- Name: $3; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_record
    ADD CONSTRAINT "$3" FOREIGN KEY (format) REFERENCES rec_formatlookup(format_code) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- Name: $6; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_record
    ADD CONSTRAINT "$6" FOREIGN KEY (memberid_add) REFERENCES member(memberid) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- Name: $7; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_record
    ADD CONSTRAINT "$7" FOREIGN KEY (memberid_lastedit) REFERENCES member(memberid) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- Name: auth_lookupid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth
    ADD CONSTRAINT auth_lookupid_fkey FOREIGN KEY (lookupid) REFERENCES l_action(typeid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: auth_memberid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth
    ADD CONSTRAINT auth_memberid_fkey FOREIGN KEY (memberid) REFERENCES member(memberid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: auth_officer_lookupid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_officer
    ADD CONSTRAINT auth_officer_lookupid_fkey FOREIGN KEY (lookupid) REFERENCES l_action(typeid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: auth_officer_officerid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_officer
    ADD CONSTRAINT auth_officer_officerid_fkey FOREIGN KEY (officerid) REFERENCES officer(officerid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: auth_subnet_typeid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_subnet
    ADD CONSTRAINT auth_subnet_typeid_fkey FOREIGN KEY (typeid) REFERENCES l_action(typeid);


--
-- Name: auth_trainingstatus_typeid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_trainingstatus
    ADD CONSTRAINT auth_trainingstatus_typeid_fkey FOREIGN KEY (typeid) REFERENCES l_action(typeid);


--
-- Name: auth_user_groups_group_id_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_user_groups
    ADD CONSTRAINT auth_user_groups_group_id_fkey FOREIGN KEY (group_id) REFERENCES auth_group(id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: baps_item_fileitemid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_item
    ADD CONSTRAINT baps_item_fileitemid_fkey FOREIGN KEY (fileitemid) REFERENCES baps_fileitem(fileitemid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: baps_item_libraryitemid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_item
    ADD CONSTRAINT baps_item_libraryitemid_fkey FOREIGN KEY (libraryitemid) REFERENCES baps_libraryitem(libraryitemid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: baps_item_listingid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_item
    ADD CONSTRAINT baps_item_listingid_fkey FOREIGN KEY (listingid) REFERENCES baps_listing(listingid) ON DELETE CASCADE;


--
-- Name: baps_item_textitemid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_item
    ADD CONSTRAINT baps_item_textitemid_fkey FOREIGN KEY (textitemid) REFERENCES baps_textitem(textitemid) ON DELETE CASCADE;


--
-- Name: baps_show_userid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY baps_show
    ADD CONSTRAINT baps_show_userid_fkey FOREIGN KEY (userid) REFERENCES baps_user(userid) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: l_presenterstatus; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_trainingstatus
    ADD CONSTRAINT l_presenterstatus FOREIGN KEY (presenterstatusid) REFERENCES l_presenterstatus(presenterstatusid);


--
-- Name: l_presenterstatus_can_award_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY l_presenterstatus
    ADD CONSTRAINT l_presenterstatus_can_award_fkey FOREIGN KEY (can_award) REFERENCES l_presenterstatus(presenterstatusid);


--
-- Name: l_presenterstatus_depends_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY l_presenterstatus
    ADD CONSTRAINT l_presenterstatus_depends_fkey FOREIGN KEY (depends) REFERENCES l_presenterstatus(presenterstatusid);


--
-- Name: mail_alias_list_listid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_alias_list
    ADD CONSTRAINT mail_alias_list_listid_fkey FOREIGN KEY (listid) REFERENCES mail_list(listid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mail_alias_member_memberid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_alias_member
    ADD CONSTRAINT mail_alias_member_memberid_fkey FOREIGN KEY (memberid) REFERENCES member(memberid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mail_alias_officer_officerid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_alias_officer
    ADD CONSTRAINT mail_alias_officer_officerid_fkey FOREIGN KEY (officerid) REFERENCES officer(officerid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: member_mail_listid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_subscription
    ADD CONSTRAINT member_mail_listid_fkey FOREIGN KEY (listid) REFERENCES mail_list(listid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: member_mail_memberid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY mail_subscription
    ADD CONSTRAINT member_mail_memberid_fkey FOREIGN KEY (memberid) REFERENCES member(memberid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: member_news_feed_memberid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_news_feed
    ADD CONSTRAINT member_news_feed_memberid_fkey FOREIGN KEY (memberid) REFERENCES member(memberid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: member_news_feed_newsentryid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_news_feed
    ADD CONSTRAINT member_news_feed_newsentryid_fkey FOREIGN KEY (newsentryid) REFERENCES news_feed(newsentryid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: member_pass_memberid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_pass
    ADD CONSTRAINT member_pass_memberid_fkey FOREIGN KEY (memberid) REFERENCES member(memberid) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- Name: member_presenterstatus_presenterstatusid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_presenterstatus
    ADD CONSTRAINT member_presenterstatus_presenterstatusid_fkey FOREIGN KEY (presenterstatusid) REFERENCES l_presenterstatus(presenterstatusid) ON DELETE RESTRICT;


--
-- Name: member_presenterstatus_revokedby_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_presenterstatus
    ADD CONSTRAINT member_presenterstatus_revokedby_fkey FOREIGN KEY (revokedby) REFERENCES member(memberid);


--
-- Name: member_profile_photo_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member
    ADD CONSTRAINT member_profile_photo_fkey FOREIGN KEY (profile_photo) REFERENCES myury.photos(photoid);


--
-- Name: member_year_memberid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY member_year
    ADD CONSTRAINT member_year_memberid_fkey FOREIGN KEY (memberid) REFERENCES member(memberid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: net_switchport_tags_portid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY net_switchport_tags
    ADD CONSTRAINT net_switchport_tags_portid_fkey FOREIGN KEY (portid) REFERENCES net_switchport(portid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: net_switchport_tags_vlanid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY net_switchport_tags
    ADD CONSTRAINT net_switchport_tags_vlanid_fkey FOREIGN KEY (vlanid) REFERENCES net_vlan(vlanid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: net_switchport_vlan_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY net_switchport
    ADD CONSTRAINT net_switchport_vlan_fkey FOREIGN KEY (vlanid) REFERENCES net_vlan(vlanid) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: news_feed_feedid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY news_feed
    ADD CONSTRAINT news_feed_feedid_fkey FOREIGN KEY (feedid) REFERENCES l_newsfeed(feedid);


--
-- Name: news_feed_memberid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY news_feed
    ADD CONSTRAINT news_feed_memberid_fkey FOREIGN KEY (memberid) REFERENCES member(memberid);


--
-- Name: nipsweb_migrate_memberid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY nipsweb_migrate
    ADD CONSTRAINT nipsweb_migrate_memberid_fkey FOREIGN KEY (memberid) REFERENCES member(memberid);


--
-- Name: rec_itunes_trackid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_itunes
    ADD CONSTRAINT rec_itunes_trackid_fkey FOREIGN KEY (trackid) REFERENCES rec_track(trackid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: rec_track_digitisedby_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_track
    ADD CONSTRAINT rec_track_digitisedby_fkey FOREIGN KEY (digitisedby) REFERENCES member(memberid) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: rec_trackcorrection_reviewedby_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_trackcorrection
    ADD CONSTRAINT rec_trackcorrection_reviewedby_fkey FOREIGN KEY (reviewedby) REFERENCES member(memberid);


--
-- Name: rec_trackcorrection_trackid_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY rec_trackcorrection
    ADD CONSTRAINT rec_trackcorrection_trackid_fkey FOREIGN KEY (trackid) REFERENCES rec_track(trackid);


--
-- Name: selector_action_fkey; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY selector
    ADD CONSTRAINT selector_action_fkey FOREIGN KEY (action) REFERENCES selector_actions(action) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: user_id_refs_id_831107f1; Type: FK CONSTRAINT; Schema: public
--

ALTER TABLE ONLY auth_user_groups
    ADD CONSTRAINT user_id_refs_id_831107f1 FOREIGN KEY (user_id) REFERENCES auth_user(id) DEFERRABLE INITIALLY DEFERRED;


SET search_path = schedule, pg_catalog;

--
-- Name: block_range_rule_block_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY block_range_rule
    ADD CONSTRAINT block_range_rule_block_id_fkey FOREIGN KEY (block_id) REFERENCES block(block_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: block_show_rule_show_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY block_show_rule
    ADD CONSTRAINT block_show_rule_show_id_fkey FOREIGN KEY (show_id) REFERENCES show(show_id) ON DELETE CASCADE;


--
-- Name: block_show_rules_block_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY block_show_rule
    ADD CONSTRAINT block_show_rules_block_id_fkey FOREIGN KEY (block_id) REFERENCES block(block_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: season_metadata_approvedid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY season_metadata
    ADD CONSTRAINT season_metadata_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: season_metadata_memberid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY season_metadata
    ADD CONSTRAINT season_metadata_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: season_metadata_metadata_key_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY season_metadata
    ADD CONSTRAINT season_metadata_metadata_key_id_fkey FOREIGN KEY (metadata_key_id) REFERENCES metadata.metadata_key(metadata_key_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: season_metadata_show_season_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY season_metadata
    ADD CONSTRAINT season_metadata_show_season_id_fkey FOREIGN KEY (show_season_id) REFERENCES show_season(show_season_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: show_credit_approvedid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_credit
    ADD CONSTRAINT show_credit_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: show_credit_credit_type_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_credit
    ADD CONSTRAINT show_credit_credit_type_id_fkey FOREIGN KEY (credit_type_id) REFERENCES people.credit_type(credit_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: show_credit_creditid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_credit
    ADD CONSTRAINT show_credit_creditid_fkey FOREIGN KEY (creditid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: show_credit_memberid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_credit
    ADD CONSTRAINT show_credit_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: show_credit_show_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_credit
    ADD CONSTRAINT show_credit_show_id_fkey FOREIGN KEY (show_id) REFERENCES show(show_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: show_genre_approvedid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_genre
    ADD CONSTRAINT show_genre_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: show_genre_genre_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_genre
    ADD CONSTRAINT show_genre_genre_id_fkey FOREIGN KEY (genre_id) REFERENCES genre(genre_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: show_genre_memberid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_genre
    ADD CONSTRAINT show_genre_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: show_genre_show_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_genre
    ADD CONSTRAINT show_genre_show_id_fkey FOREIGN KEY (show_id) REFERENCES show(show_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: show_image_metadata_approvedid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_image_metadata
    ADD CONSTRAINT show_image_metadata_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: show_image_metadata_memberid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_image_metadata
    ADD CONSTRAINT show_image_metadata_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: show_image_metadata_metadata_key_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_image_metadata
    ADD CONSTRAINT show_image_metadata_metadata_key_id_fkey FOREIGN KEY (metadata_key_id) REFERENCES metadata.metadata_key(metadata_key_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: show_image_metadata_show_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_image_metadata
    ADD CONSTRAINT show_image_metadata_show_id_fkey FOREIGN KEY (show_id) REFERENCES show(show_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: show_location_approvedid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_location
    ADD CONSTRAINT show_location_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: show_location_location_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_location
    ADD CONSTRAINT show_location_location_id_fkey FOREIGN KEY (location_id) REFERENCES location(location_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: show_location_memberid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_location
    ADD CONSTRAINT show_location_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: show_location_show_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_location
    ADD CONSTRAINT show_location_show_id_fkey FOREIGN KEY (show_id) REFERENCES show(show_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: show_memberid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show
    ADD CONSTRAINT show_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: show_metadata_approvedid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_metadata
    ADD CONSTRAINT show_metadata_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: show_metadata_memberid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_metadata
    ADD CONSTRAINT show_metadata_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: show_metadata_metadata_key_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_metadata
    ADD CONSTRAINT show_metadata_metadata_key_id_fkey FOREIGN KEY (metadata_key_id) REFERENCES metadata.metadata_key(metadata_key_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: show_metadata_show_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_metadata
    ADD CONSTRAINT show_metadata_show_id_fkey FOREIGN KEY (show_id) REFERENCES show(show_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: show_podcast_link_podcast_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_podcast_link
    ADD CONSTRAINT show_podcast_link_podcast_id_fkey FOREIGN KEY (podcast_id) REFERENCES uryplayer.podcast(podcast_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: show_podcast_link_show_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_podcast_link
    ADD CONSTRAINT show_podcast_link_show_id_fkey FOREIGN KEY (show_id) REFERENCES show(show_id);


--
-- Name: show_season_requested_time_show_season_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_season_requested_time
    ADD CONSTRAINT show_season_requested_time_show_season_id_fkey FOREIGN KEY (show_season_id) REFERENCES show_season(show_season_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: show_season_requested_week_show_season_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_season_requested_week
    ADD CONSTRAINT show_season_requested_week_show_season_id_fkey FOREIGN KEY (show_season_id) REFERENCES show_season(show_season_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: show_season_show_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_season
    ADD CONSTRAINT show_season_show_id_fkey FOREIGN KEY (show_id) REFERENCES show(show_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: show_season_termid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_season
    ADD CONSTRAINT show_season_termid_fkey FOREIGN KEY (termid) REFERENCES public.terms(termid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: show_season_timeslot_approvedid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_season_timeslot
    ADD CONSTRAINT show_season_timeslot_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: show_season_timeslot_memberid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_season_timeslot
    ADD CONSTRAINT show_season_timeslot_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: show_season_timeslot_show_season_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show_season_timeslot
    ADD CONSTRAINT show_season_timeslot_show_season_id_fkey FOREIGN KEY (show_season_id) REFERENCES show_season(show_season_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: show_show_type_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY show
    ADD CONSTRAINT show_show_type_id_fkey FOREIGN KEY (show_type_id) REFERENCES show_type(show_type_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: timeslot_metadata_approvedid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY timeslot_metadata
    ADD CONSTRAINT timeslot_metadata_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: timeslot_metadata_memberid_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY timeslot_metadata
    ADD CONSTRAINT timeslot_metadata_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: timeslot_metadata_metadata_key_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY timeslot_metadata
    ADD CONSTRAINT timeslot_metadata_metadata_key_id_fkey FOREIGN KEY (metadata_key_id) REFERENCES metadata.metadata_key(metadata_key_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: timeslot_metadata_show_timeslot_id_fkey; Type: FK CONSTRAINT; Schema: schedule
--

ALTER TABLE ONLY timeslot_metadata
    ADD CONSTRAINT timeslot_metadata_show_timeslot_id_fkey FOREIGN KEY (show_season_timeslot_id) REFERENCES show_season_timeslot(show_season_timeslot_id) ON UPDATE CASCADE ON DELETE CASCADE;


SET search_path = sis2, pg_catalog;

--
-- Name: member_options_memberid_fkey; Type: FK CONSTRAINT; Schema: sis2
--

ALTER TABLE ONLY member_options
    ADD CONSTRAINT member_options_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid);


--
-- Name: member_signin_memberid_fkey; Type: FK CONSTRAINT; Schema: sis2
--

ALTER TABLE ONLY member_signin
    ADD CONSTRAINT member_signin_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) ON DELETE SET NULL;


--
-- Name: member_signin_signerid_fkey; Type: FK CONSTRAINT; Schema: sis2
--

ALTER TABLE ONLY member_signin
    ADD CONSTRAINT member_signin_signerid_fkey FOREIGN KEY (signerid) REFERENCES public.member(memberid) ON DELETE SET NULL;


--
-- Name: messages_commtypeid_fkey; Type: FK CONSTRAINT; Schema: sis2
--

ALTER TABLE ONLY messages
    ADD CONSTRAINT messages_commtypeid_fkey FOREIGN KEY (commtypeid) REFERENCES commtype(commtypeid) ON DELETE SET NULL;


--
-- Name: messages_statusid_fkey; Type: FK CONSTRAINT; Schema: sis2
--

ALTER TABLE ONLY messages
    ADD CONSTRAINT messages_statusid_fkey FOREIGN KEY (statusid) REFERENCES statustype(statusid) ON DELETE SET NULL;


--
-- Name: messages_timeslotid_fkey; Type: FK CONSTRAINT; Schema: sis2
--

ALTER TABLE ONLY messages
    ADD CONSTRAINT messages_timeslotid_fkey FOREIGN KEY (timeslotid) REFERENCES schedule.show_season_timeslot(show_season_timeslot_id) ON DELETE CASCADE;


SET search_path = tracklist, pg_catalog;

--
-- Name: bapsaudiologid; Type: FK CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY tracklist
    ADD CONSTRAINT bapsaudiologid FOREIGN KEY (bapsaudioid) REFERENCES public.baps_audiolog(audiologid) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: selbaps_bapsloc_fkey; Type: FK CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY selbaps
    ADD CONSTRAINT selbaps_bapsloc_fkey FOREIGN KEY (bapsloc) REFERENCES public.baps_server(serverid);


--
-- Name: selbaps_selaction_fkey; Type: FK CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY selbaps
    ADD CONSTRAINT selbaps_selaction_fkey FOREIGN KEY (selaction) REFERENCES public.selector_actions(action);


--
-- Name: sourceid; Type: FK CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY tracklist
    ADD CONSTRAINT sourceid FOREIGN KEY (source) REFERENCES source(sourceid) ON UPDATE CASCADE;


--
-- Name: stateid; Type: FK CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY tracklist
    ADD CONSTRAINT stateid FOREIGN KEY (state) REFERENCES state(stateid) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: timeslotid; Type: FK CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY tracklist
    ADD CONSTRAINT timeslotid FOREIGN KEY (timeslotid) REFERENCES schedule.show_season_timeslot(show_season_timeslot_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: track_notrec_audiologid_fkey; Type: FK CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY track_notrec
    ADD CONSTRAINT track_notrec_audiologid_fkey FOREIGN KEY (audiologid) REFERENCES tracklist(audiologid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: track_rec_audiologid_fkey; Type: FK CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY track_rec
    ADD CONSTRAINT track_rec_audiologid_fkey FOREIGN KEY (audiologid) REFERENCES tracklist(audiologid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: track_rec_trackid_fkey; Type: FK CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY track_rec
    ADD CONSTRAINT track_rec_trackid_fkey FOREIGN KEY (trackid) REFERENCES public.rec_track(trackid);


SET search_path = uryplayer, pg_catalog;

--
-- Name: package_id_refs_package_id_f71dbbff; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_package_entry
    ADD CONSTRAINT package_id_refs_package_id_f71dbbff FOREIGN KEY (package_id) REFERENCES metadata.package(package_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_approvedid_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast
    ADD CONSTRAINT podcast_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_credit_approvedid_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_credit
    ADD CONSTRAINT podcast_credit_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_credit_credit_type_id_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_credit
    ADD CONSTRAINT podcast_credit_credit_type_id_fkey FOREIGN KEY (credit_type_id) REFERENCES people.credit_type(credit_type_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_credit_creditid_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_credit
    ADD CONSTRAINT podcast_credit_creditid_fkey FOREIGN KEY (creditid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_credit_memberid_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_credit
    ADD CONSTRAINT podcast_credit_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_credit_podcast_id_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_credit
    ADD CONSTRAINT podcast_credit_podcast_id_fkey FOREIGN KEY (podcast_id) REFERENCES podcast(podcast_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_image_metadata_approvedid_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_image_metadata
    ADD CONSTRAINT podcast_image_metadata_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_image_metadata_memberid_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_image_metadata
    ADD CONSTRAINT podcast_image_metadata_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_image_metadata_metadata_key_id_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_image_metadata
    ADD CONSTRAINT podcast_image_metadata_metadata_key_id_fkey FOREIGN KEY (metadata_key_id) REFERENCES metadata.metadata_key(metadata_key_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_image_metadata_podcast_id_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_image_metadata
    ADD CONSTRAINT podcast_image_metadata_podcast_id_fkey FOREIGN KEY (podcast_id) REFERENCES podcast(podcast_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_memberid_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast
    ADD CONSTRAINT podcast_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_metadata_approvedid_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_metadata
    ADD CONSTRAINT podcast_metadata_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_metadata_memberid_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_metadata
    ADD CONSTRAINT podcast_metadata_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_metadata_metadata_key_id_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_metadata
    ADD CONSTRAINT podcast_metadata_metadata_key_id_fkey FOREIGN KEY (metadata_key_id) REFERENCES metadata.metadata_key(metadata_key_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_metadata_podcast_id_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_metadata
    ADD CONSTRAINT podcast_metadata_podcast_id_fkey FOREIGN KEY (podcast_id) REFERENCES podcast(podcast_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_package_entry_approvedid_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_package_entry
    ADD CONSTRAINT podcast_package_entry_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_package_entry_memberid_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_package_entry
    ADD CONSTRAINT podcast_package_entry_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: podcast_package_entry_podcast_id_fkey; Type: FK CONSTRAINT; Schema: uryplayer
--

ALTER TABLE ONLY podcast_package_entry
    ADD CONSTRAINT podcast_package_entry_podcast_id_fkey FOREIGN KEY (podcast_id) REFERENCES podcast(podcast_id) DEFERRABLE INITIALLY DEFERRED;


SET search_path = webcam, pg_catalog;

--
-- Name: memberviews_memberid_fkey; Type: FK CONSTRAINT; Schema: webcam
--

ALTER TABLE ONLY memberviews
    ADD CONSTRAINT memberviews_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) ON UPDATE CASCADE ON DELETE CASCADE;


SET search_path = website, pg_catalog;

--
-- Name: banner_banner_type_id_fkey; Type: FK CONSTRAINT; Schema: website
--

ALTER TABLE ONLY banner
    ADD CONSTRAINT banner_banner_type_id_fkey FOREIGN KEY (banner_type_id) REFERENCES banner_type(banner_type_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: banner_campaign_approvedid_fkey; Type: FK CONSTRAINT; Schema: website
--

ALTER TABLE ONLY banner_campaign
    ADD CONSTRAINT banner_campaign_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: banner_campaign_banner_id_fkey; Type: FK CONSTRAINT; Schema: website
--

ALTER TABLE ONLY banner_campaign
    ADD CONSTRAINT banner_campaign_banner_id_fkey FOREIGN KEY (banner_id) REFERENCES banner(banner_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: banner_campaign_banner_location_id_fkey; Type: FK CONSTRAINT; Schema: website
--

ALTER TABLE ONLY banner_campaign
    ADD CONSTRAINT banner_campaign_banner_location_id_fkey FOREIGN KEY (banner_location_id) REFERENCES banner_location(banner_location_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: banner_campaign_memberid_fkey; Type: FK CONSTRAINT; Schema: website
--

ALTER TABLE ONLY banner_campaign
    ADD CONSTRAINT banner_campaign_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: banner_photo_id_fkey; Type: FK CONSTRAINT; Schema: website
--

ALTER TABLE ONLY banner
    ADD CONSTRAINT banner_photo_id_fkey FOREIGN KEY (photoid) REFERENCES myury.photos(photoid);


--
-- Name: banner_timeslot_approvedid_fkey; Type: FK CONSTRAINT; Schema: website
--

ALTER TABLE ONLY banner_timeslot
    ADD CONSTRAINT banner_timeslot_approvedid_fkey FOREIGN KEY (approvedid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: banner_timeslot_banner_campaign_id_fkey; Type: FK CONSTRAINT; Schema: website
--

ALTER TABLE ONLY banner_timeslot
    ADD CONSTRAINT banner_timeslot_banner_campaign_id_fkey FOREIGN KEY (banner_campaign_id) REFERENCES banner_campaign(banner_campaign_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: banner_timeslot_memberid_fkey; Type: FK CONSTRAINT; Schema: website
--

ALTER TABLE ONLY banner_timeslot
    ADD CONSTRAINT banner_timeslot_memberid_fkey FOREIGN KEY (memberid) REFERENCES public.member(memberid) DEFERRABLE INITIALLY DEFERRED;


CREATE SCHEMA myradio;
SET search_path = myradio, pg_catalog;
CREATE TABLE schema (
    attr character varying NOT NULL,
    value integer NOT NULL
);
INSERT INTO schema VALUES ('version', 0);
ALTER TABLE ONLY schema
    ADD CONSTRAINT schema_pkey PRIMARY KEY (attr);

SET search_path = public;
INSERT INTO public.l_college (descr) VALUES ('Unknown');
INSERT INTO myury.services (name, enabled) VALUES ('MyRadio', true);
INSERT INTO l_status VALUES ('c', 'Current');
INSERT INTO l_status VALUES ('h', 'Historic');
INSERT INTO rec_statuslookup VALUES ('l', 'lost');
INSERT INTO rec_statuslookup VALUES ('u', 'unknown');
INSERT INTO rec_statuslookup VALUES ('o', 'OK');
INSERT INTO rec_statuslookup VALUES ('d', 'digital only');
INSERT INTO rec_medialookup VALUES ('c', 'CD');
INSERT INTO rec_medialookup VALUES ('2', 'Vinyl 12"');
INSERT INTO rec_medialookup VALUES ('7', 'Vinyl 7"');
INSERT INTO rec_medialookup VALUES ('n', 'NIPSWeb MP3 Import');
INSERT INTO rec_cleanlookup VALUES ('u', 'unknown');
INSERT INTO rec_cleanlookup VALUES ('y', 'yes');
INSERT INTO rec_cleanlookup VALUES ('n', '<b>NO!</b>');
INSERT INTO rec_formatlookup VALUES ('a', 'Album');
INSERT INTO rec_formatlookup VALUES ('s', 'Single');
INSERT INTO rec_genrelookup VALUES ('p', 'Pop');
INSERT INTO rec_genrelookup VALUES ('r', 'Rock');
INSERT INTO rec_genrelookup VALUES ('d', 'Dance');
INSERT INTO rec_genrelookup VALUES ('c', 'Classical');
INSERT INTO rec_genrelookup VALUES ('z', 'Production');
INSERT INTO rec_genrelookup VALUES ('h', 'Rap / Hip-Hop');
INSERT INTO rec_genrelookup VALUES ('o', 'Other');

--------------
-- Set Credit Types
--------------

SET search_path = people, pg_catalog;

--
-- Data for Name: credit_type; Type: TABLE DATA; Schema: people
--

INSERT INTO credit_type VALUES (1, 'Presenter', 'Presenters', true);
INSERT INTO credit_type VALUES (2, 'Producer', 'Producers', false);
INSERT INTO credit_type VALUES (3, 'Voice Actor', 'Voice Actors', false);
INSERT INTO credit_type VALUES (4, 'Director', 'Directors', false);
INSERT INTO credit_type VALUES (5, 'Editor', 'Editors', false);
INSERT INTO credit_type VALUES (6, 'Trainer', 'Trainers', false);
INSERT INTO credit_type VALUES (7, 'Attendee', 'Attendees', false);
INSERT INTO credit_type VALUES (8, 'Reporter', 'Reporters', false);
INSERT INTO credit_type VALUES (9, 'Engineer', 'Engineers', false);


--
-- Name: schedule.showcredittype_id_seq; Type: SEQUENCE SET; Schema: people
--

SELECT pg_catalog.setval('"schedule.showcredittype_id_seq"', 9, true);

--------------
-- Set Genres
--------------
SET search_path = schedule, pg_catalog;

--
-- Data for Name: genre; Type: TABLE DATA; Schema: schedule
--

INSERT INTO genre VALUES (1, 'Anything Goes');
INSERT INTO genre VALUES (2, 'Classical');
INSERT INTO genre VALUES (3, 'Electronic');
INSERT INTO genre VALUES (4, 'Experimental');
INSERT INTO genre VALUES (5, 'Folk');
INSERT INTO genre VALUES (6, 'Hip-Hop');
INSERT INTO genre VALUES (7, 'International');
INSERT INTO genre VALUES (8, 'Jazz');
INSERT INTO genre VALUES (9, 'Novelty');
INSERT INTO genre VALUES (10, 'Pop');
INSERT INTO genre VALUES (11, 'Rock');
INSERT INTO genre VALUES (12, 'Soul/R&B');
INSERT INTO genre VALUES (13, 'Speech');
INSERT INTO genre VALUES (14, 'Indie');
INSERT INTO genre VALUES (15, 'Dance');
INSERT INTO genre VALUES (16, 'Metal');
INSERT INTO genre VALUES (17, 'Retro');


--
-- Name: genre_genre_id_seq; Type: SEQUENCE SET; Schema: schedule
--

SELECT pg_catalog.setval('genre_genre_id_seq', 17, true);

INSERT INTO show_type VALUES (1, 'Show', true, true, '', true, false);
INSERT INTO show_type VALUES (2, 'Demo', false, true, '', false, false);
INSERT INTO show_type VALUES (3, 'Training Lecture', false, true, '', false, false);
INSERT INTO show_type VALUES (4, 'Meeting', false, true, '', false, false);
INSERT INTO show_type VALUES (7, 'Interview', false, true, '', false, false);
INSERT INTO show_type VALUES (6, 'Recording', false, true, '', false, false);
INSERT INTO show_type VALUES (8, 'Filler', true, false, 'Used by LASS to determine which show is the filler/jukebox/sustainer show.  EXACTLY ONE SHOW AT ANY GIVEN TIME MUST BE OF THIS TYPE', false, true);
INSERT INTO show_type VALUES (9, 'Show Block', false, true, 'It''s for shows within a show - like the 40 Hour Show breaking down into little blocks.', false, false);


--
-- Name: show_type_show_type_id_seq; Type: SEQUENCE SET; Schema: schedule; Owner: web
--

SELECT pg_catalog.setval('show_type_show_type_id_seq', 9, true);

INSERT INTO location VALUES (1, 'Studio 1');
SELECT pg_catalog.setval('location_location_id_seq', 2, true);

SET search_path = metadata, pg_catalog;

--
-- Data for Name: metadata_key; Type: TABLE DATA; Schema: metadata
--

INSERT INTO metadata_key VALUES (5, 'guest', true, '', 300, NULL, false);
INSERT INTO metadata_key VALUES (3, 'ob_location', true, '', 300, NULL, false);
INSERT INTO metadata_key VALUES (6, 'image', false, '', 300, NULL, false);
INSERT INTO metadata_key VALUES (13, 'css-normal', true, 'The name of a CSS/HTML class that should be applied to representations of this item on the website.  This class is intended for use in "normal" contexts, such as lists and detail pages, where any branding or styling should be moderated.', 300, NULL, false);
INSERT INTO metadata_key VALUES (14, 'css-emphasis', false, 'The name of a CSS/HTML class that should be applied to representations of this item on the website.  This class is intended for use in "emphasised" contexts, such as on schedules or headers, where styling and branding should be prominent.', 300, NULL, false);
INSERT INTO metadata_key VALUES (7, 'singular', false, '(Applicable to group items only.)  When applicable (the item defines a group of other items), the singular noun form of one of those items.  For example, the ''singular'' of ''Station Management'' would be ''Station Manager''.  See also ''title'' and ''plural''.', 300, NULL, false);
INSERT INTO metadata_key VALUES (8, 'plural', false, '(Applicable to group items only.)  When applicable (the item defines a group of other items), the plural noun form of a subgroup of those items.  For example, the ''plural'' of ''Station Management'' would be ''Station Managers''.  See also ''title'' and ''singular''.', 300, NULL, false);
INSERT INTO metadata_key VALUES (9, 'title_image', false, 'When applicable and defined, title_image will be used instead of title when displaying the heading for this item.  The expected dimensions of the image depend on the context.', 300, NULL, false);
INSERT INTO metadata_key VALUES (10, 'thumbnail_image', false, '(Image) When defined and applicable (for example when the item is a show or podcast or other listable), this image will appear as a thumbnail in media lists.', 300, NULL, false);
INSERT INTO metadata_key VALUES (11, 'player_image', false, '(Image) Image displayed on players (for podcasts this is jwplayer, for shows this is radioplayer).  Dimensions depend on the item and which player it is to be shown on, but as a rule of thumb this is larger than thumbnail_image and square.', 300, NULL, false);
INSERT INTO metadata_key VALUES (12, 'internal_note', true, 'Metadata with this key will be saved with the item but not shown on the public site; this should be used to attach internal, private notes to items.  For example, notes to the Programme Controller regarding show application detail should be tagged with this key.', 300, NULL, false);
INSERT INTO metadata_key VALUES (15, 'short_title', false, 'Like title, but shorter.  Use this for abbreviated versions of long titles; on-site it''s used for website TITLE tags and suchlike.', 300, NULL, false);
INSERT INTO metadata_key VALUES (4, 'tag', true, '', 300, 'Tags', true);
INSERT INTO metadata_key VALUES (2, 'title', false, 'The publicly available title of the item.  For items defining a group (for example, roles and credits) this is a singular, collective name (''Station Management'', ''Presentership''); see also the ''singular'' and ''plural'' keys.', 300, 'Titles', true);
INSERT INTO metadata_key VALUES (1, 'description', false, 'A human-readable, general public description of the item.  Where this description is used depends on the item type, but this key usually defines the most detailed publicly available description text associated with the item.', 300, 'Descriptions', true);
INSERT INTO metadata_key VALUES (16, 'reject-reason', false, 'Reason for Season Application Rejection', 300, 'Reasons for Season Application Rejection', false);
INSERT INTO metadata_key VALUES (17, 'upload_state', false, 'When uploading data to services, this is a store of the upload state', 300, NULL, false);


--
-- Name: metadata_key_metadata_key_id_seq; Type: SEQUENCE SET; Schema: metadata
--

SELECT pg_catalog.setval('metadata_key_metadata_key_id_seq', 17, true);

CREATE TABLE music.explicit_checked (
    trackid integer NOT NULL
);

ALTER TABLE ONLY music.explicit_checked ADD CONSTRAINT explicit_checked_pkey PRIMARY KEY (trackid);
ALTER TABLE music.explicit_checked ADD CONSTRAINT explicit_checked_fkey FOREIGN KEY (trackid) REFERENCES public.rec_track(trackid) ON DELETE CASCADE;

SET search_path = myury, pg_catalog;

INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_Track', 'Track');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_Show', 'Show');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_Season', 'Season');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_Timeslot', 'Timeslot');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_Album', 'Album');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_Demo', 'Demo');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_List', 'List');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_Photo', 'Photo');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_Podcast', 'Podcast');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_Scheduler', 'Scheduler');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_TrackCorrection', 'TrackCorrection');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_TrainingStatus', 'Training');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_UserTrainingStatus', 'UserTraining');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_Selector', 'Selector');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_Alias', 'Alias');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_Officer', 'Officer');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_Team', 'Team');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_TracklistItem', 'TracklistItem');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_User', 'User');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_Swagger', 'resources');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\ServiceAPI\MyRadio_Artist', 'Artist');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\iTones\iTones_Playlist', 'Playlist');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\iTones\iTones_Utils', 'iTones');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\MyRadio\CoreUtils', 'Utils');
INSERT INTO api_class_map (class_name, api_name) VALUES ('\MyRadio\MyRadio\AuthUtils', 'AuthUtils');

INSERT INTO api_method_auth (class_name, method_name, typeid) VALUES ('\MyRadio\ServiceAPI\MyRadio_Swagger', NULL, NULL);
INSERT INTO api_method_auth (class_name, method_name, typeid) VALUES ('\MyRadio\ServiceAPI\MyRadio_Timeslot', 'getWeekSchedule', NULL);

SET search_path = tracklist, pg_catalog;
INSERT INTO tracklist.source (sourceid, source) VALUES ('b', 'BAPS');
INSERT INTO tracklist.source (sourceid, source) VALUES ('m', 'Manual');
INSERT INTO tracklist.source (sourceid, source) VALUES ('o', 'Other');
INSERT INTO tracklist.source (sourceid, source) VALUES ('j', 'Jukebox');

SET search_path = public, pg_catalog;
CREATE TABLE myury.api_mixin_auth (
    api_mixin_auth_id SERIAL,
    class_name CHARACTER VARYING NOT NULL,
    mixin_name CHARACTER VARYING,
    typeid INT REFERENCES l_action(typeid)
);
