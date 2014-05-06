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
COMMENT ON SCHEMA sis2 IS 'Used by Studio Infomation Service. Deprecated somewhat.';
CREATE SCHEMA tracklist;
COMMENT ON SCHEMA tracklist IS 'Provides a schema that logs played out tracks for PPL track returns';
CREATE SCHEMA uryplayer;
COMMENT ON SCHEMA uryplayer IS 'URY Player';
CREATE SCHEMA webcam;
CREATE SCHEMA website;
COMMENT ON SCHEMA website IS 'Collection of data relating to the operation of the public-facing website.';
CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;
CREATE FUNCTION bapstotracklist() RETURNS trigger
    LANGUAGE plpgsql
    AS $$DECLARE
audid INTEGER;
BEGIN
	IF ((TG_OP = 'UPDATE')
        AND ((SELECT COUNT(*) FROM (SELECT sel.action FROM selector sel WHERE sel.action >= 4 AND sel.action <= 11 ORDER BY sel.time DESC LIMIT 1)AS seltop INNER JOIN tracklist.selbaps bsel ON (seltop.action = bsel.selaction) WHERE bsel.bapsloc = NEW."serverid" AND (NEW."timeplayed" >= (SELECT sel.time FROM selector sel ORDER BY sel.time DESC LIMIT 1) ) ) = 1 )
        AND ((SELECT COUNT(*) FROM baps_audio ba WHERE ba.audioid = NEW."audioid" AND ba.trackid > 0 ) = 1 )
        AND ((NEW."timestopped" - NEW."timeplayed" > '00:00:30') )
        AND ((SELECT COUNT(*) FROM tracklist.tracklist WHERE bapsaudioid = NEW."audiologid") = 0)
        )
        
 THEN
        INSERT INTO tracklist.tracklist (source, timestart, timestop, timeslotid, bapsaudioid)
                VALUES ('b', NEW."timeplayed", NEW."timestopped", (SELECT show_season_timeslot_id FROM schedule.show_season_timeslot WHERE start_time <= NOW() AND (start_time + duration) >= NOW()ORDER BY show_season_timeslot_id ASC LIMIT 1 ), NEW."audiologid" )
                RETURNING audiologid INTO audid;
	INSERT INTO tracklist.track_rec
                VALUES ("audid", (SELECT rec.recordid FROM rec_track rec INNER JOIN baps_audio ba USING (trackid)
                WHERE ba.audioid = NEW."audioid"), (SELECT trackid FROM baps_audio WHERE audioid = NEW."audioid"));
 
		
	END IF;
	RETURN NULL;
END $$;
CREATE FUNCTION clear_item_func() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
IF OLD.textitemid IS NOT NULL THEN
	DELETE FROM baps_textitem WHERE textitemid=OLD.textitemid;
END IF;
IF OLD.libraryitemid IS NOT NULL THEN
	DELETE FROM baps_libraryitem WHERE libraryitemid=OLD.libraryitemid;
END IF;
IF OLD.fileitemid IS NOT NULL THEN
	DELETE FROM baps_fileitem WHERE fileitemid=OLD.fileitemid;
END IF;
RETURN OLD;
END;
$$;
CREATE FUNCTION process_gammu_text() RETURNS trigger
    LANGUAGE plpgsql
    AS $$    BEGIN
        IF (TG_OP = 'INSERT') THEN
           IF (SELECT show_season_timeslot_id FROM schedule.show_season_timeslot WHERE start_time <= NOW() AND (start_time + duration) >= NOW() ORDER BY show_season_timeslot_id ASC LIMIT 1) IS NOT NULL THEN
              INSERT INTO sis2.messages (commtypeid, timeslotid, sender, subject, content, statusid)
              
              VALUES (2, (SELECT show_season_timeslot_id FROM schedule.show_season_timeslot WHERE start_time <= NOW() AND (start_time + duration) >= NOW() ORDER BY show_season_timeslot_id ASC LIMIT 1), NEW."SenderNumber", NEW."TextDecoded", NEW."TextDecoded", 1);
              RETURN NEW;
           ELSE
              INSERT INTO sis2.messages (commtypeid, timeslotid, sender, subject, content, statusid)
              VALUES (2, 118540, NEW."SenderNumber", NEW."TextDecoded", NEW."TextDecoded", 1);
              RETURN NEW;
           END IF;
        END IF;
        RETURN NULL;
    END;
$$;
CREATE FUNCTION set_shelfcode_func() RETURNS trigger
    LANGUAGE plpgsql
    AS $$DECLARE
    myshelfnumber integer DEFAULT 0;
    recordrow RECORD;
BEGIN
    IF ((NEW.media='7' OR NEW.media='2') AND NEW.format='a') THEN
        FOR recordrow IN SELECT * FROM rec_record WHERE media=NEW.media AND (format='7' OR format='2') AND shelfletter=NEW.shelfletter ORDER BY shelfnumber LOOP
	        IF recordrow.shelfnumber > myshelfnumber+1 THEN
			EXIT;
		END IF;
		myshelfnumber = myshelfnumber + 1;
    	END LOOP;
    ELSE
        FOR recordrow IN SELECT * FROM rec_record WHERE media=NEW.media AND format=NEW.format AND shelfletter=NEW.shelfletter ORDER BY shelfnumber LOOP
	        IF recordrow.shelfnumber > myshelfnumber+1 THEN
			EXIT;
		END IF;
		myshelfnumber = myshelfnumber + 1;
    	END LOOP;
    END IF;
    NEW.shelfnumber = myshelfnumber + 1;
RETURN NEW;
END;
$$;
CREATE FUNCTION update_timestamp() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
  BEGIN
    NEW."UpdatedInDB" := LOCALTIMESTAMP(0);
    RETURN NEW;
  END;
$$;
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
CREATE TABLE bapsplanner.client_ids (
    client_id integer NOT NULL,
    show_season_timeslot_id integer,
    session_id character varying(64)
);
COMMENT ON TABLE bapsplanner.client_ids IS 'Enables tracking of individual windows instead of sessions - a session may have more than one window open.';
CREATE SEQUENCE bapsplanner.client_ids_client_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE bapsplanner.client_ids_client_id_seq OWNED BY bapsplanner.client_ids.client_id;
CREATE TABLE bapsplanner.managed_items (
    manageditemid integer NOT NULL,
    managedplaylistid integer NOT NULL,
    title character varying NOT NULL,
    length time without time zone,
    bpm smallint,
    expirydate date,
    memberid integer
);
CREATE SEQUENCE bapsplanner.managed_items_manageditemid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE bapsplanner.managed_items_manageditemid_seq OWNED BY bapsplanner.managed_items.manageditemid;
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
    weight integer NOT NULL,
    playlistid character varying(15) NOT NULL,
    day smallint NOT NULL,
    start_time time with time zone NOT NULL,
    end_time time with time zone NOT NULL
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
CREATE TABLE requests (
    "user" integer NOT NULL,
    start timestamp with time zone NOT NULL,
    "end" timestamp with time zone NOT NULL,
    format character varying(4) NOT NULL,
    title character varying(64),
    removed boolean DEFAULT false NOT NULL
);
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
CREATE TABLE api_key_auth (
    key_string character varying NOT NULL,
    typeid integer NOT NULL
);
COMMENT ON TABLE api_key_auth IS 'Stores what API capabilities each key has.';
CREATE SEQUENCE api_key_log_api_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
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
CREATE TABLE contract_versions (
    contract_version_id integer NOT NULL,
    description text,
    ratified_date date,
    enforced_date date
);
COMMENT ON TABLE contract_versions IS 'Stores the revision history of URY''s presenter contract. This way we can ensure presenters have signed the latest version.';
COMMENT ON COLUMN contract_versions.description IS 'A description of revisions made in this version of the contract.';
COMMENT ON COLUMN contract_versions.ratified_date IS 'The date the new version of the contract came into use.';
COMMENT ON COLUMN contract_versions.enforced_date IS 'If NULL, then it isn''t required for presenters to have signed the new contract yet.';
CREATE SEQUENCE contract_versions_contract_version_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE contract_versions_contract_version_id_seq OWNED BY contract_versions.contract_version_id;
CREATE TABLE error_rate (
    request_id integer NOT NULL,
    server_ip character varying NOT NULL,
    error_count integer NOT NULL,
    exception_count integer NOT NULL,
    "timestamp" timestamp without time zone DEFAULT now() NOT NULL,
    queries integer DEFAULT 0 NOT NULL
);
COMMENT ON TABLE error_rate IS 'Stores error and exception counts for every request.';
COMMENT ON COLUMN error_rate.queries IS 'Number of queries executed, not errors :P';
CREATE SEQUENCE error_rate_request_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE error_rate_request_id_seq OWNED BY error_rate.request_id;
CREATE TABLE menu_columns (
    columnid integer NOT NULL,
    title character varying(50),
    "position" smallint
);
COMMENT ON TABLE menu_columns IS 'Stores the column headers for the Menu page';
CREATE SEQUENCE menu_columns_columnid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE menu_columns_columnid_seq OWNED BY menu_columns.columnid;
CREATE TABLE menu_links (
    itemid integer NOT NULL,
    sectionid integer NOT NULL,
    title character varying(100) NOT NULL,
    url character varying(150) NOT NULL,
    description text
);
COMMENT ON TABLE menu_links IS 'Stores simple menu items';
CREATE SEQUENCE menu_links_itemid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE menu_links_itemid_seq OWNED BY menu_links.itemid;
CREATE TABLE menu_module (
    menumoduleid integer NOT NULL,
    moduleid integer NOT NULL,
    title character varying NOT NULL,
    url character varying NOT NULL,
    description character varying
);
COMMENT ON TABLE menu_module IS 'Submenus for modules';
CREATE SEQUENCE menu_module_menumoduleid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE menu_module_menumoduleid_seq OWNED BY menu_module.menumoduleid;
CREATE TABLE menu_sections (
    sectionid integer NOT NULL,
    columnid integer,
    title character varying(50),
    "position" smallint
);
COMMENT ON TABLE menu_sections IS 'Stores section headings for the Menu screen';
CREATE SEQUENCE menu_sections_sectionid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE menu_sections_sectionid_seq OWNED BY menu_sections.sectionid;
CREATE TABLE menu_twigitems (
    twigitemid integer NOT NULL,
    sectionid integer NOT NULL,
    template character varying(100) NOT NULL
);
COMMENT ON TABLE menu_twigitems IS 'Stores twig templates that will appear under menu sections, e.g. for the Get On A Show checklist';
CREATE SEQUENCE menu_twigitems_twigitemid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE menu_twigitems_twigitemid_seq OWNED BY menu_twigitems.twigitemid;
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
CREATE TABLE acl_member (
    privilegeid integer NOT NULL,
    memberid integer,
    class text NOT NULL,
    verb text NOT NULL,
    scope integer DEFAULT 0 NOT NULL
);
COMMENT ON TABLE acl_member IS 'Access Control List for regular members (used to grant temporary and extraordinary permissions)';
COMMENT ON COLUMN acl_member.privilegeid IS 'The unique ID of this ACL row.';
COMMENT ON COLUMN acl_member.memberid IS 'The unique ID of the member being granted a permission.';
COMMENT ON COLUMN acl_member.class IS 'The full name of the Rapier object class a permission is being granted for.';
COMMENT ON COLUMN acl_member.verb IS 'The verb (eg "read") representing the privileged action being granted.';
COMMENT ON COLUMN acl_member.scope IS 'The scope of this action being granted. (see constants_acl_scope).';
CREATE SEQUENCE acl_member_privilegeid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE acl_member_privilegeid_seq OWNED BY acl_member.privilegeid;
CREATE TABLE acl_officer (
    privilegeid integer DEFAULT nextval('acl_member_privilegeid_seq'::regclass) NOT NULL,
    officerid integer NOT NULL,
    class text NOT NULL,
    verb text NOT NULL,
    scope integer DEFAULT 0 NOT NULL
);
COMMENT ON COLUMN acl_officer.class IS 'The full name of the Rapier object class a permission is being granted for.';
CREATE SEQUENCE acl_officer_privilegeid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
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
CREATE TABLE auth_permission (
    id integer NOT NULL,
    name character varying(50) NOT NULL,
    content_type_id integer NOT NULL,
    codename character varying(100) NOT NULL
);
CREATE SEQUENCE auth_permission_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE auth_permission_id_seq OWNED BY auth_permission.id;
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
    lastfm_verified boolean DEFAULT false
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
CREATE TABLE cat_view_promoter (
    chicken integer,
    nipples character varying
);
COMMENT ON TABLE cat_view_promoter IS 'Don''t delete, or the cataloguer breaks.';
CREATE TABLE chart (
    chartweek integer NOT NULL,
    lastweek text NOT NULL,
    title text NOT NULL,
    artist text NOT NULL,
    "position" integer NOT NULL
);
COMMENT ON TABLE chart IS 'ury chart rundowns';
COMMENT ON COLUMN chart.chartweek IS 'chart release timestamp';
CREATE TABLE client (
    clientid integer NOT NULL,
    name text NOT NULL,
    secrethash text NOT NULL,
    description text
);
COMMENT ON TABLE client IS 'Registry of clients allowed to use Rapier.';
COMMENT ON COLUMN client.clientid IS 'The numeric ID of the client.';
COMMENT ON COLUMN client.name IS 'The log-in name of the client.';
COMMENT ON COLUMN client.secrethash IS 'A hash of the log-in password of the client.';
COMMENT ON COLUMN client.description IS 'A human-readable description of the client.';
CREATE SEQUENCE client_clientid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE client_clientid_seq OWNED BY client.clientid;
CREATE TABLE constants_acl_scope (
    scopeid integer NOT NULL,
    description text NOT NULL
);
COMMENT ON TABLE constants_acl_scope IS 'Human-readable descriptions for the ACL scope levels.';
COMMENT ON COLUMN constants_acl_scope.scopeid IS 'The unique ID of this scope level.';
COMMENT ON COLUMN constants_acl_scope.description IS 'The human-readable description of this scope level.';
CREATE SEQUENCE constants_acl_scope_scopeid_seq
    START WITH 3
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE constants_acl_scope_scopeid_seq OWNED BY constants_acl_scope.scopeid;
CREATE TABLE selector (
    selid integer NOT NULL,
    "time" timestamp without time zone DEFAULT now() NOT NULL,
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
COMMENT ON TABLE l_newsfeed IS 'Lookup table for internal news feeds';
CREATE SEQUENCE l_newsfeeds_feedid_seq
    START WITH 1
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
    auth_provider character varying
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
CREATE TABLE sched_blocks (
    blockid integer NOT NULL,
    name character(255) NOT NULL,
    use_timerange boolean DEFAULT false NOT NULL,
    start_hour integer,
    end_hour integer,
    has_slot_tab boolean DEFAULT false NOT NULL,
    id_string text,
    is_flagship boolean DEFAULT false NOT NULL,
    identifier text DEFAULT 'default'::text NOT NULL
);
COMMENT ON TABLE sched_blocks IS 'Information about specialist blocks, mainly to help the schedule renderer.
The scheduler "sees" which shows fall into which blocks in the following ways:
1) If id_string is provided, any shows with a summary (name) containing id_string as a substring will be marked as part of the block.
2) If use_timerange is TRUE and a start_hour and end_hour are provided, then all shows between those hours will be marked as part of the block.
Blocks with no timerange are prioritised over blocks with a timerange, so that eg. a new lunchtime Speech show added to the database will override Flagship show colouring.
(TODO: Manual linkage between blockid and sched_entry.entryid if the situation with duplicate/redundant blocks gets out of hand.)';
COMMENT ON COLUMN sched_blocks.blockid IS 'Sequential identification for block.';
COMMENT ON COLUMN sched_blocks.name IS 'The name of the block.  This is shown on the scheduler tab (if any).';
COMMENT ON COLUMN sched_blocks.use_timerange IS 'Whether or not the scheduler should use a time-range to identify shows in this block as well as the name.';
COMMENT ON COLUMN sched_blocks.start_hour IS 'The hour at which this block begins (NB: midnight is hour 25, 1am is hour 26, etc).';
COMMENT ON COLUMN sched_blocks.end_hour IS 'The hour at which this block ends (NB: midnight is hour 25, 1am is hour 26, etc).';
COMMENT ON COLUMN sched_blocks.has_slot_tab IS 'If true, a "tab" will appear on the hour marker of the first hour of the block with the block colour and name.';
COMMENT ON COLUMN sched_blocks.id_string IS 'The (optional) string to use for searching for block members.  If this is non-NULL, any show with this string in its summary (title) will become part of the block.';
COMMENT ON COLUMN sched_blocks.is_flagship IS 'If true, block is a flagship block and will be emphasised as such in lists etc.';
COMMENT ON COLUMN sched_blocks.identifier IS 'A string used to identify the block in areas such as the colour coding system.  Many different blocks can share the same identifier.';
CREATE SEQUENCE sched_blocks_blockid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE sched_blocks_blockid_seq OWNED BY sched_blocks.blockid;
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
CREATE VIEW sched_entry AS
    SELECT t0.entryid, t0.entrytypeid, t0.rss, t0.url, t1.summary, t1.createddate, NULL::unknown AS oid, t1.summary AS description FROM ((SELECT show.show_id AS entryid, '3' AS entrytypeid, NULL::unknown AS rss, NULL::unknown AS url FROM schedule.show) t0 LEFT JOIN (SELECT show_metadata.show_id AS entryid, show_metadata.metadata_value AS summary, show_metadata.effective_from AS createddate FROM schedule.show_metadata WHERE ((show_metadata.metadata_key_id = 2) AND ((show_metadata.effective_to IS NULL) OR (show_metadata.effective_to >= now())))) t1 ON ((t0.entryid = t1.entryid)));
SET default_with_oids = true;
CREATE TABLE sched_entrytype (
    entrytypeid integer NOT NULL,
    entrytypename character varying(30) NOT NULL,
    causesclash boolean DEFAULT true,
    takesuptime boolean DEFAULT false
);
COMMENT ON COLUMN sched_entrytype.takesuptime IS 'defines if this entrytype appears in a persons schedule';
CREATE SEQUENCE sched_entrytype_entrytypeid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE sched_entrytype_entrytypeid_seq OWNED BY sched_entrytype.entrytypeid;
CREATE SEQUENCE sched_genre_genreid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
SET default_with_oids = false;
CREATE TABLE sched_genre (
    genreid integer DEFAULT nextval('sched_genre_genreid_seq'::regclass) NOT NULL,
    genrename character varying(30) NOT NULL
);
COMMENT ON COLUMN sched_genre.genreid IS 'The unique ID number of the genre.';
COMMENT ON COLUMN sched_genre.genrename IS 'The name of the genre.';
SET default_with_oids = true;
CREATE TABLE sched_musiccategory (
    musiccategoryid integer NOT NULL,
    musiccategoryname character varying(30) NOT NULL,
    ordering integer
);
CREATE SEQUENCE sched_musiccategory_musiccategoryid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE sched_musiccategory_musiccategoryid_seq OWNED BY sched_musiccategory.musiccategoryid;
CREATE TABLE sched_room (
    roomid integer NOT NULL,
    roomname character varying(30) NOT NULL,
    ordering integer
);
CREATE SEQUENCE sched_room_roomid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE sched_room_roomid_seq OWNED BY sched_room.roomid;
CREATE TABLE sched_speechcategory (
    speechcategoryid integer NOT NULL,
    speechcategoryname character varying(30) NOT NULL,
    ordering integer
);
CREATE SEQUENCE sched_speechcategory_speechcategoryid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE sched_speechcategory_speechcategoryid_seq OWNED BY sched_speechcategory.speechcategoryid;
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
CREATE SEQUENCE sis_comm_track_comm_trackid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 100000
    CACHE 1;
SET default_with_oids = true;
CREATE TABLE sis_commtype (
    commtypeid integer NOT NULL,
    descr character varying(16) NOT NULL
);
CREATE SEQUENCE sis_commtype_commtypeid_seq
    START WITH 6
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE sis_commtype_commtypeid_seq OWNED BY sis_commtype.commtypeid;
SET default_with_oids = false;
CREATE TABLE sis_piss (
    id integer NOT NULL,
    body text,
    "timestamp" timestamp without time zone,
    memberid integer
);
COMMENT ON TABLE sis_piss IS 'SIS2 Presenter Information Sheets';
CREATE SEQUENCE sis_piss_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE sis_piss_id_seq OWNED BY sis_piss.id;
SET default_with_oids = true;
CREATE TABLE sis_status (
    statusid integer NOT NULL,
    descr character varying(8)
);
CREATE SEQUENCE sis_status_statusid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE sis_status_statusid_seq OWNED BY sis_status.statusid;
CREATE SEQUENCE sis_type_typeid_seq
    START WITH 6
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 1000
    CACHE 1;
SET default_with_oids = false;
CREATE TABLE site_package (
    packageid integer NOT NULL,
    name text NOT NULL,
    active boolean DEFAULT false NOT NULL,
    starttime timestamp without time zone,
    endtime timestamp without time zone
);
COMMENT ON TABLE site_package IS 'Table defining special event packages, which temporarily replace the site look and feel.
Packages reside in /content/packages/*NAME* and must include the following files:
1) package.css - a CSS file patching the normal URY CSS to add event-specific changes such as a different banner.
2) index.php - a PHP file that will replace the home page while the package is active.  This will usually redirect to the actual home page for the event.';
COMMENT ON COLUMN site_package.packageid IS 'The unique identifier of the package.  The package with the highest packageid (and active set to TRUE) is the one that will be applied to the site.';
COMMENT ON COLUMN site_package.name IS 'The name of the package, which also denotes the subdirectory of /content/packages/ that contains the package files.';
COMMENT ON COLUMN site_package.active IS 'When TRUE, the package is active.  There should generally be only one active package; in the case that there are multiple the one with the highest packageid is selected.';
CREATE SEQUENCE site_special_package_packageid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE site_special_package_packageid_seq OWNED BY site_package.packageid;
CREATE TABLE sitetree_tree (
    id integer NOT NULL,
    title character varying(100) NOT NULL,
    alias character varying(80) NOT NULL
);
CREATE SEQUENCE sitetree_tree_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE sitetree_tree_id_seq OWNED BY sitetree_tree.id;
CREATE TABLE sitetree_treeitem (
    id integer NOT NULL,
    title character varying(100) NOT NULL,
    hint character varying(200) NOT NULL,
    url character varying(200) NOT NULL,
    urlaspattern boolean NOT NULL,
    tree_id integer NOT NULL,
    hidden boolean NOT NULL,
    alias character varying(80),
    description text NOT NULL,
    inmenu boolean NOT NULL,
    inbreadcrumbs boolean NOT NULL,
    insitetree boolean NOT NULL,
    access_loggedin boolean NOT NULL,
    access_restricted boolean NOT NULL,
    access_perm_type integer NOT NULL,
    parent_id integer,
    sort_order integer NOT NULL
);
CREATE TABLE sitetree_treeitem_access_permissions (
    id integer NOT NULL,
    treeitem_id integer NOT NULL,
    permission_id integer NOT NULL
);
CREATE SEQUENCE sitetree_treeitem_access_permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE sitetree_treeitem_access_permissions_id_seq OWNED BY sitetree_treeitem_access_permissions.id;
CREATE SEQUENCE sitetree_treeitem_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
ALTER SEQUENCE sitetree_treeitem_id_seq OWNED BY sitetree_treeitem.id;
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
    metadata_value character varying(100) NOT NULL,
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
    start_time time with time zone NOT NULL,
    end_time time with time zone,
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
ALTER TABLE ONLY contract_versions ALTER COLUMN contract_version_id SET DEFAULT nextval('contract_versions_contract_version_id_seq'::regclass);
ALTER TABLE ONLY error_rate ALTER COLUMN request_id SET DEFAULT nextval('error_rate_request_id_seq'::regclass);
ALTER TABLE ONLY menu_columns ALTER COLUMN columnid SET DEFAULT nextval('menu_columns_columnid_seq'::regclass);
ALTER TABLE ONLY menu_links ALTER COLUMN itemid SET DEFAULT nextval('menu_links_itemid_seq'::regclass);
ALTER TABLE ONLY menu_module ALTER COLUMN menumoduleid SET DEFAULT nextval('menu_module_menumoduleid_seq'::regclass);
ALTER TABLE ONLY menu_sections ALTER COLUMN sectionid SET DEFAULT nextval('menu_sections_sectionid_seq'::regclass);
ALTER TABLE ONLY menu_twigitems ALTER COLUMN twigitemid SET DEFAULT nextval('menu_twigitems_twigitemid_seq'::regclass);
ALTER TABLE ONLY modules ALTER COLUMN moduleid SET DEFAULT nextval('modules_moduleid_seq'::regclass);
ALTER TABLE ONLY photos ALTER COLUMN photoid SET DEFAULT nextval('photos_photoid_seq'::regclass);
ALTER TABLE ONLY services ALTER COLUMN serviceid SET DEFAULT nextval('services_serviceid_seq'::regclass);
ALTER TABLE ONLY services_versions ALTER COLUMN serviceversionid SET DEFAULT nextval('services_versions_serviceversionid_seq'::regclass);
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
ALTER TABLE ONLY acl_member ALTER COLUMN privilegeid SET DEFAULT nextval('acl_member_privilegeid_seq'::regclass);
ALTER TABLE ONLY auth_group ALTER COLUMN id SET DEFAULT nextval('auth_group_id_seq'::regclass);
ALTER TABLE ONLY auth_permission ALTER COLUMN id SET DEFAULT nextval('auth_permission_id_seq'::regclass);
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
ALTER TABLE ONLY client ALTER COLUMN clientid SET DEFAULT nextval('client_clientid_seq'::regclass);
ALTER TABLE ONLY constants_acl_scope ALTER COLUMN scopeid SET DEFAULT nextval('constants_acl_scope_scopeid_seq'::regclass);
ALTER TABLE ONLY l_newsfeed ALTER COLUMN feedid SET DEFAULT nextval('l_newsfeeds_feedid_seq'::regclass);
ALTER TABLE ONLY l_presenterstatus ALTER COLUMN presenterstatusid SET DEFAULT nextval('l_presenterstatus_presenterstatusid_seq'::regclass);
ALTER TABLE ONLY mail_alias_text ALTER COLUMN aliasid SET DEFAULT nextval('mail_aliasid_seq'::regclass);
ALTER TABLE ONLY mail_list ALTER COLUMN listid SET DEFAULT nextval('mail_list_listid_seq'::regclass);
ALTER TABLE ONLY member_news_feed ALTER COLUMN membernewsfeedid SET DEFAULT nextval('member_news_feed_membernewsfeedid_seq'::regclass);
ALTER TABLE ONLY member_presenterstatus ALTER COLUMN memberpresenterstatusid SET DEFAULT nextval('member_presenterstatus_memberpresenterstatusid_seq'::regclass);
ALTER TABLE ONLY news_feed ALTER COLUMN newsentryid SET DEFAULT nextval('news_feed_newsentryid_seq'::regclass);
ALTER TABLE ONLY rec_trackcorrection ALTER COLUMN correctionid SET DEFAULT nextval('rec_trackcorrection_correctionid_seq'::regclass);
ALTER TABLE ONLY sched_blocks ALTER COLUMN blockid SET DEFAULT nextval('sched_blocks_blockid_seq'::regclass);
ALTER TABLE ONLY sched_entrytype ALTER COLUMN entrytypeid SET DEFAULT nextval('sched_entrytype_entrytypeid_seq'::regclass);
ALTER TABLE ONLY sched_musiccategory ALTER COLUMN musiccategoryid SET DEFAULT nextval('sched_musiccategory_musiccategoryid_seq'::regclass);
ALTER TABLE ONLY sched_room ALTER COLUMN roomid SET DEFAULT nextval('sched_room_roomid_seq'::regclass);
ALTER TABLE ONLY sched_speechcategory ALTER COLUMN speechcategoryid SET DEFAULT nextval('sched_speechcategory_speechcategoryid_seq'::regclass);
ALTER TABLE ONLY selector ALTER COLUMN selid SET DEFAULT nextval('selector_selid_seq'::regclass);
ALTER TABLE ONLY selector_actions ALTER COLUMN action SET DEFAULT nextval('selector_actions_action_seq'::regclass);
ALTER TABLE ONLY sis_commtype ALTER COLUMN commtypeid SET DEFAULT nextval('sis_commtype_commtypeid_seq'::regclass);
ALTER TABLE ONLY sis_piss ALTER COLUMN id SET DEFAULT nextval('sis_piss_id_seq'::regclass);
ALTER TABLE ONLY sis_status ALTER COLUMN statusid SET DEFAULT nextval('sis_status_statusid_seq'::regclass);
ALTER TABLE ONLY site_package ALTER COLUMN packageid SET DEFAULT nextval('site_special_package_packageid_seq'::regclass);
ALTER TABLE ONLY sitetree_tree ALTER COLUMN id SET DEFAULT nextval('sitetree_tree_id_seq'::regclass);
ALTER TABLE ONLY sitetree_treeitem ALTER COLUMN id SET DEFAULT nextval('sitetree_treeitem_id_seq'::regclass);
ALTER TABLE ONLY sitetree_treeitem_access_permissions ALTER COLUMN id SET DEFAULT nextval('sitetree_treeitem_access_permissions_id_seq'::regclass);
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
ALTER TABLE ONLY member_signin ALTER COLUMN member_signin_id SET DEFAULT nextval('member_signin_member_signin_id_seq'::regclass);
ALTER TABLE ONLY messages ALTER COLUMN commid SET DEFAULT nextval('messages_commid_seq'::regclass);
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
SET search_path = myury, pg_catalog;
ALTER TABLE ONLY api_key
    ADD CONSTRAINT api_key_pkey PRIMARY KEY (key_string);
CREATE TABLE api_key_log (
    api_log_id integer NOT NULL,
    key_string character varying NOT NULL,
    "timestamp" timestamp without time zone DEFAULT now() NOT NULL,
    remote_ip inet NOT NULL,
    request_path character varying,
    request_params json
);
COMMENT ON TABLE api_key_log IS 'Stores a record of API Requests by an API Key';
ALTER SEQUENCE api_key_log_api_log_id_seq OWNED BY api_key_log.api_log_id;
ALTER TABLE ONLY api_key_log ALTER COLUMN api_log_id SET DEFAULT nextval('api_key_log_api_log_id_seq'::regclass);
ALTER TABLE ONLY api_key_log
    ADD CONSTRAINT api_key_log_pkey PRIMARY KEY (api_log_id);
CREATE INDEX api_key_log_timestamp_index ON api_key_log USING btree ("timestamp");
ALTER TABLE ONLY api_key_log
    ADD CONSTRAINT api_key_log_key_string_fkey FOREIGN KEY (key_string) REFERENCES api_key(key_string);
CREATE SCHEMA myradio;
SET search_path = myradio, pg_catalog;
CREATE TABLE schema (
    attr character varying NOT NULL,
    value integer NOT NULL
);
INSERT INTO schema VALUES ('version', 0);
ALTER TABLE ONLY schema
    ADD CONSTRAINT schema_pkey PRIMARY KEY (attr);