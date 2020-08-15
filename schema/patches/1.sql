BEGIN;

CREATE TABLE jukebox.playlist_categories (
    id SERIAL PRIMARY KEY,
    name TEXT,
    description TEXT
);

INSERT INTO jukebox.playlist_categories (id, name, description)
VALUES (
    1,
    'General',
    '<p>This category is for all playlists that don''t fit the others. They will not be played by Jukebox or Campus Playout.</p>'
), (
    2,
    'Jukebox',
    '<p>This category is for all playlists that should be played by Jukebox.</p>'
);

ALTER SEQUENCE jukebox.playlist_categories_id_seq
  START 3;

ALTER TABLE jukebox.playlists
ADD COLUMN category INTEGER DEFAULT 1;

ALTER TABLE jukebox.playlists
ADD CONSTRAINT fk_playlist_category FOREIGN KEY(category) REFERENCES jukebox.playlist_categories(id);

COMMIT;