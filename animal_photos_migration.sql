-- Add status field to animal_photos table
ALTER TABLE animal_photos 
ADD COLUMN status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending' AFTER photo_path,
ADD COLUMN reviewed_by INT(11) NULL AFTER status,
ADD COLUMN reviewed_at TIMESTAMP NULL AFTER reviewed_by,
ADD COLUMN rejection_reason TEXT NULL AFTER reviewed_at;

-- Add foreign key for reviewed_by
ALTER TABLE animal_photos 
ADD CONSTRAINT fk_animal_photos_reviewed_by 
FOREIGN KEY (reviewed_by) REFERENCES users(user_id);