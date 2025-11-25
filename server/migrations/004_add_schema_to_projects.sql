-- Add schema column to projects table for storing user-defined log fields
ALTER TABLE projects
ADD COLUMN schema JSON DEFAULT NULL COMMENT 'User-defined log fields schema';
