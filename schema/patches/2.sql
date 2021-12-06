BEGIN;

-- Give the option for podcasts to be explicit (true or false).
-- This is used when generating RSS feeds for iTunes.
ALTER TABLE schedule.show
    ADD COLUMN podcast_explicit BOOLEAN DEFAULT 'f';

COMMENT ON COLUMN schedule.show.podcast_explicit IS
'If this show is a podcast, whether it contains explicit content.';

COMMIT;
