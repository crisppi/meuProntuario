/*
  Adds a data_realizacao column to the exame table so the UI can persist the collection date
  when an exam is created before lab results exist.
  Run this once against the target schema.
*/
ALTER TABLE exame
  ADD COLUMN data_realizacao DATE;
