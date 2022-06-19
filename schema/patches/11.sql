-- Allow Jukebox playlists to be archived (true/false)
ALTER TABLE jukebox.playlists
	ADD archived BOOL DEFAULT FALSE NOT NULL;
