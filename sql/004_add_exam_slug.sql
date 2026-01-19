/*
  Adds a normalized slug so we can detect duplicates independent of case/accents.
*/
ALTER TABLE exame
  ADD COLUMN slug VARCHAR(255) UNIQUE;
