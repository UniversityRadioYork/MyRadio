ALTER TABLE member
    ADD COLUMN nname character varying(255);

INSERT INTO metadata.metadata_key VALUES (20,'upload_starttime',false,'In the case where a manual upload is required (because an event started late) will need to start late. This is a UTC time.',300,false);    
INSERT INTO metadata.metadata_key VALUES (21,'upload_endtime',false,'In the case where a manual upload is required (because an event started late) will need to finish early/late.',300,false);
