CREATE TRIGGER blocks_after_insert
AFTER INSERT ON blocks
FOR EACH ROW
    DELETE FROM follows
    WHERE (follower_id = NEW.blocker_id AND following_id = NEW.blocked_id)
       OR (follower_id = NEW.blocked_id AND following_id = NEW.blocker_id);

CREATE TRIGGER blocks_after_update
AFTER UPDATE ON blocks
FOR EACH ROW
    DELETE FROM follows
    WHERE (follower_id = NEW.blocker_id AND following_id = NEW.blocked_id)
       OR (follower_id = NEW.blocked_id AND following_id = NEW.blocker_id);

DELIMITER //
CREATE TRIGGER follows_before_insert
BEFORE INSERT ON follows
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM blocks
        WHERE (blocker_id = NEW.follower_id AND blocked_id = NEW.following_id)
           OR (blocker_id = NEW.following_id AND blocked_id = NEW.follower_id)
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A block prevents this follow.';
    END IF;
END//

CREATE TRIGGER follows_before_update
BEFORE UPDATE ON follows
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM blocks
        WHERE (blocker_id = NEW.follower_id AND blocked_id = NEW.following_id)
           OR (blocker_id = NEW.following_id AND blocked_id = NEW.follower_id)
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A block prevents this follow.';
    END IF;
END//
DELIMITER ;