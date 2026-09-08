-- Run once for the Code IDE exam/practice enhancements.
ALTER TABLE practice_sessions
    ADD COLUMN de_file_path VARCHAR(255) NULL AFTER mo_ta,
    ADD COLUMN de_file_name VARCHAR(255) NULL AFTER de_file_path;
