-- AtletaLab · MySQL 8.0.16+ / MariaDB 10.6+
-- Importar UNA VEZ en una base vacía seleccionada previamente.
-- No crea ni elimina bases de datos. Todas las contraseñas semilla son
-- hashes de una clave aleatoria no publicada. Inicializar el administrador:
-- php bin/cake.php admin_password admin@atletalab.test
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(200) NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE archetypes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL UNIQUE,
    incPercentage DECIMAL(5,2) NOT NULL,
    stagnationPenalty DECIMAL(5,2) NOT NULL,
    deloadPercentage DECIMAL(5,2) NOT NULL,
    maxFatigueThreshold DECIMAL(8,2) NOT NULL,
    rpeLimit DECIMAL(3,1) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT ck_archetypes_inc CHECK (incPercentage BETWEEN 0.50 AND 50.00),
    CONSTRAINT ck_archetypes_penalty CHECK (stagnationPenalty BETWEEN 0.50 AND 50.00),
    CONSTRAINT ck_archetypes_deload CHECK (deloadPercentage BETWEEN 0.50 AND 50.00),
    CONSTRAINT ck_archetypes_fatigue CHECK (maxFatigueThreshold > 0 AND maxFatigueThreshold <= 10000),
    CONSTRAINT ck_archetypes_rpe CHECK (rpeLimit BETWEEN 5.0 AND 9.5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(200) NOT NULL UNIQUE,
    firstName VARCHAR(50) NOT NULL,
    lastName VARCHAR(50) NOT NULL,
    password VARCHAR(200) NOT NULL,
    birthDate DATE NOT NULL,
    roleId INT NOT NULL,
    archetypeId INT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (roleId) REFERENCES roles(id),
    CONSTRAINT fk_users_archetype FOREIGN KEY (archetypeId) REFERENCES archetypes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE objectives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    targetRepMin INT NOT NULL,
    targetRepMax INT NOT NULL,
    minSuccessRate DECIMAL(5,2) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT ck_objectives_min CHECK (targetRepMin BETWEEN 1 AND 50),
    CONSTRAINT ck_objectives_max CHECK (targetRepMax BETWEEN 1 AND 50),
    CONSTRAINT ck_objectives_order CHECK (targetRepMax >= targetRepMin),
    CONSTRAINT ck_objectives_success CHECK (minSuccessRate BETWEEN 50.00 AND 100.00)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    muscleGroup ENUM('Pecho', 'Espalda', 'Pierna', 'Hombro', 'Brazo', 'Core') NOT NULL,
    fatigueCoefficient DECIMAL(4,2) NOT NULL,
    description VARCHAR(200) NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT ck_exercises_coefficient CHECK (fatigueCoefficient > 0.10 AND fatigueCoefficient < 5.00)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE routines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(100) NOT NULL,
    description TEXT NULL,
    status ENUM('Activa', 'En Descarga', 'Estancada', 'Completada') NOT NULL DEFAULT 'Activa',
    athleteId INT NOT NULL,
    trainerId INT NOT NULL,
    objectiveId INT NOT NULL,
    effectivenessScore DECIMAL(5,2) DEFAULT 0.00,
    isBestPractice BOOLEAN DEFAULT FALSE,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_routines_athlete FOREIGN KEY (athleteId) REFERENCES users(id),
    CONSTRAINT fk_routines_trainer FOREIGN KEY (trainerId) REFERENCES users(id),
    CONSTRAINT fk_routines_objective FOREIGN KEY (objectiveId) REFERENCES objectives(id),
    CONSTRAINT ck_routines_distinct_users CHECK (athleteId <> trainerId),
    CONSTRAINT ck_routines_score CHECK (effectivenessScore BETWEEN 0.00 AND 100.00)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE routine_exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    targetSets INT NOT NULL,
    targetReps INT NOT NULL,
    currentWeight DECIMAL(6,2) NOT NULL,
    projectedWeight DECIMAL(6,2) NOT NULL,
    routineId INT NOT NULL,
    exerciseId INT NOT NULL,
    CONSTRAINT fk_routine_exercises_routine FOREIGN KEY (routineId) REFERENCES routines(id) ON DELETE CASCADE,
    CONSTRAINT fk_routine_exercises_exercise FOREIGN KEY (exerciseId) REFERENCES exercises(id),
    CONSTRAINT ck_routine_exercises_sets CHECK (targetSets > 0),
    CONSTRAINT ck_routine_exercises_reps CHECK (targetReps > 0),
    CONSTRAINT ck_routine_exercises_weight CHECK (currentWeight >= 0 AND projectedWeight >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE workout_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setsCompleted INT NOT NULL,
    repsAchieved INT NOT NULL,
    weightUsed DECIMAL(6,2) NOT NULL,
    rpe DECIMAL(3,1) NOT NULL,
    loggedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    routineExerciseId INT NOT NULL,
    CONSTRAINT fk_workout_logs_assignment FOREIGN KEY (routineExerciseId) REFERENCES routine_exercises(id) ON DELETE CASCADE,
    CONSTRAINT ck_workout_logs_sets CHECK (setsCompleted > 0),
    CONSTRAINT ck_workout_logs_reps CHECK (repsAchieved >= 0),
    CONSTRAINT ck_workout_logs_weight CHECK (weightUsed >= 0),
    CONSTRAINT ck_workout_logs_rpe CHECK (rpe > 1.0 AND rpe < 10.0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

START TRANSACTION;
INSERT INTO roles (id, code, name, description) VALUES
    (1, 'ADMIN', 'Administrador', 'Administra catálogos, usuarios y parámetros del Core.'),
    (2, 'TRAINER', 'Entrenador', 'Planifica y supervisa las rutinas de los atletas.'),
    (3, 'ATHLETE', 'Atleta', 'Registra su entrenamiento y progresa según su arquetipo.');

INSERT INTO archetypes (id, code, name, incPercentage, stagnationPenalty, deloadPercentage, maxFatigueThreshold, rpeLimit) VALUES
    (1, 'INICIAL', 'Adaptación inicial', 2.50, 5.00, 20.00, 140.00, 7.5),
    (2, 'INTERMEDIO', 'Progresión sostenida', 5.00, 7.50, 25.00, 200.00, 8.5),
    (3, 'AVANZADO', 'Rendimiento avanzado', 2.50, 10.00, 30.00, 260.00, 9.0);

INSERT INTO users (id, email, firstName, lastName, password, birthDate, roleId, archetypeId) VALUES
    (1, 'admin@atletalab.test', 'Admin', 'AtletaLab', '$2y$12$UYRfXkeJd2oJbSbSNvK6GOAEybe7K37mqnVUYj8ANtyYPtV0dkcOe', '1990-06-15', 1, NULL),
    (2, 'valeria@atletalab.test', 'Valeria', 'Torres', '$2y$12$UYRfXkeJd2oJbSbSNvK6GOAEybe7K37mqnVUYj8ANtyYPtV0dkcOe', '1992-08-10', 2, NULL),
    (3, 'andres@atletalab.test', 'Andrés', 'Vega', '$2y$12$UYRfXkeJd2oJbSbSNvK6GOAEybe7K37mqnVUYj8ANtyYPtV0dkcOe', '1988-03-22', 2, NULL),
    (4, 'lucia@atletalab.test', 'Lucía', 'Mendoza', '$2y$12$UYRfXkeJd2oJbSbSNvK6GOAEybe7K37mqnVUYj8ANtyYPtV0dkcOe', '2001-04-18', 3, 1),
    (5, 'mateo@atletalab.test', 'Mateo', 'Salazar', '$2y$12$UYRfXkeJd2oJbSbSNvK6GOAEybe7K37mqnVUYj8ANtyYPtV0dkcOe', '1998-11-06', 3, 2),
    (6, 'camila@atletalab.test', 'Camila', 'Ríos', '$2y$12$UYRfXkeJd2oJbSbSNvK6GOAEybe7K37mqnVUYj8ANtyYPtV0dkcOe', '1996-02-29', 3, 3),
    (7, 'daniel@atletalab.test', 'Daniel', 'Paredes', '$2y$12$UYRfXkeJd2oJbSbSNvK6GOAEybe7K37mqnVUYj8ANtyYPtV0dkcOe', '2002-07-12', 3, 1);

INSERT INTO objectives (id, code, name, targetRepMin, targetRepMax, minSuccessRate) VALUES
    (1, 'FUERZA', 'Fuerza máxima', 3, 6, 85.00),
    (2, 'HIPERTROFIA', 'Hipertrofia muscular', 8, 12, 80.00),
    (3, 'RESISTENCIA', 'Resistencia muscular', 15, 20, 75.00);

INSERT INTO exercises (id, code, name, muscleGroup, fatigueCoefficient, description) VALUES
    (1, 'PRESS_BANCA', 'Press de banca', 'Pecho', 1.50, 'Empuje horizontal con barra y control del descenso.'),
    (2, 'APERTURAS', 'Aperturas con mancuernas', 'Pecho', 0.85, 'Aducción horizontal con carga moderada.'),
    (3, 'REMO_BARRA', 'Remo con barra', 'Espalda', 1.60, 'Tracción horizontal con columna estable.'),
    (4, 'JALON_PECHO', 'Jalón al pecho', 'Espalda', 1.10, 'Tracción vertical en polea.'),
    (5, 'SENTADILLA', 'Sentadilla con barra', 'Pierna', 2.10, 'Patrón dominante de rodilla con rango controlado.'),
    (6, 'PESO_RUMANO', 'Peso muerto rumano', 'Pierna', 1.85, 'Bisagra de cadera con énfasis posterior.'),
    (7, 'PRESS_MILITAR', 'Press militar', 'Hombro', 1.35, 'Empuje vertical con barra.'),
    (8, 'ELEV_LATERAL', 'Elevaciones laterales', 'Hombro', 0.70, 'Abducción de hombro con mancuernas.'),
    (9, 'CURL_BICEPS', 'Curl de bíceps', 'Brazo', 0.65, 'Flexión de codo con control de la carga.'),
    (10, 'EXT_TRICEPS', 'Extensión de tríceps', 'Brazo', 0.65, 'Extensión de codo en polea.'),
    (11, 'CRUNCH_POLEA', 'Crunch en polea', 'Core', 0.60, 'Flexión controlada de tronco en polea.'),
    (12, 'PALLOF_PRESS', 'Press Pallof', 'Core', 0.55, 'Antirrotación de tronco con polea.');

INSERT INTO routines (id, code, title, description, status, athleteId, trainerId, objectiveId, effectivenessScore, isBestPractice) VALUES
    (1, 'RUT_INICIAL_A', 'Base de fuerza · Lucía', 'Adaptación técnica y progresión gradual de cargas.', 'Activa', 4, 2, 2, 82.50, FALSE),
    (2, 'RUT_DESCARGA_B', 'Recuperación · Mateo', 'Reducción temporal del volumen y de la carga.', 'En Descarga', 5, 2, 1, 76.00, FALSE),
    (3, 'RUT_HISTORICA_C', 'Fuerza consolidada · Camila', 'Bloque histórico completado con buena respuesta.', 'Completada', 6, 3, 1, 94.00, TRUE),
    (4, 'RUT_REVISION_D', 'Revisión de progreso · Daniel', 'Bloque pendiente de ajuste por estancamiento.', 'Estancada', 7, 3, 3, 61.00, FALSE);

INSERT INTO routine_exercises (id, targetSets, targetReps, currentWeight, projectedWeight, routineId, exerciseId) VALUES
    (1, 3, 10, 30.00, 30.75, 1, 1),
    (2, 3, 12, 25.00, 25.63, 1, 4),
    (3, 3, 10, 40.00, 41.00, 1, 5),
    (4, 2, 6, 50.00, 37.50, 2, 5),
    (5, 4, 5, 65.00, 66.63, 3, 1),
    (6, 4, 5, 80.00, 82.00, 3, 5),
    (7, 3, 18, 15.00, 15.38, 4, 9);

INSERT INTO workout_logs (id, setsCompleted, repsAchieved, weightUsed, rpe, routineExerciseId) VALUES
    (1, 3, 10, 30.00, 7.0, 1), (2, 3, 12, 25.00, 6.5, 2),
    (3, 3, 10, 40.00, 7.5, 3), (4, 2, 6, 37.50, 6.0, 4),
    (5, 4, 5, 65.00, 8.0, 5), (6, 4, 5, 80.00, 8.5, 6),
    (7, 3, 12, 15.00, 9.5, 7), (8, 3, 10, 30.75, 7.5, 1);
COMMIT;
