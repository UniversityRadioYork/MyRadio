BEGIN;

-- Create table for scheduling training sessions
CREATE TABLE schedule.demo
(
	demo_id SERIAL
		CONSTRAINT demo_pk
			PRIMARY KEY,
	presenterstatusid INT NOT NULL
		CONSTRAINT demo_l_presenterstatus_presenterstatusid_fk
			REFERENCES l_presenterstatus,
	demo_time TIMESTAMP WITH time zone NOT NULL,
	demo_link TEXT,
	memberid INT
		CONSTRAINT demo_member_memberid_fk
			REFERENCES member
);

-- Allow arbitrary training attendees
CREATE TABLE schedule.demo_attendee
(
	demo_id INT NOT NULL
		CONSTRAINT demo_attendee_demo_demo_id_fk
			REFERENCES schedule.demo,
	memberid INT NOT NULL
		CONSTRAINT demo_attendee_member_memberid_fk
			REFERENCES member
);

-- Allow people to express interest in getting trained
CREATE TABLE schedule.demo_waiting_list
(
	memberid INT
		CONSTRAINT demo_waiting_list_member_memberid_fk
			REFERENCES member (memberid),
	presenterstatusid INT
		CONSTRAINT demo_waiting_list_l_presenterstatus_presenterstatusid_fk
			REFERENCES l_presenterstatus (presenterstatusid),
	date_added TIMESTAMP WITH time zone
);



COMMIT;
