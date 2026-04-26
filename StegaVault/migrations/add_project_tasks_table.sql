-- Migration: Add project_tasks table for Progress Tracker feature
-- Run this against your Supabase PostgreSQL database

CREATE TABLE IF NOT EXISTS project_tasks (
    id          BIGSERIAL PRIMARY KEY,
    project_id  INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_by  INTEGER NOT NULL REFERENCES users(id),
    priority    VARCHAR(20) DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high')),
    status      VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'in_progress', 'completed')),
    progress    INTEGER DEFAULT 0 CHECK (progress >= 0 AND progress <= 100),
    due_date    DATE,
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    updated_at  TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_project_tasks_project  ON project_tasks(project_id);
CREATE INDEX IF NOT EXISTS idx_project_tasks_assigned ON project_tasks(assigned_to);

-- Auto-update updated_at on row change
CREATE OR REPLACE FUNCTION update_project_tasks_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_project_tasks_updated_at ON project_tasks;
CREATE TRIGGER trg_project_tasks_updated_at
    BEFORE UPDATE ON project_tasks
    FOR EACH ROW EXECUTE FUNCTION update_project_tasks_updated_at();
