/*
  Adds the missing columns that the updated Exam model/DAO now populate.
  Run this once against the production schema.
*/
ALTER TABLE exame
  ADD COLUMN IF NOT EXISTS tipo VARCHAR(64) DEFAULT 'laboratorial',
  ADD COLUMN IF NOT EXISTS laboratorio VARCHAR(255),
  ADD COLUMN IF NOT EXISTS frequencia VARCHAR(128),
  ADD COLUMN IF NOT EXISTS observacoes TEXT;
