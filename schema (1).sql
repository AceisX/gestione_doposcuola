-- Schema del database per la gestione doposcuola

CREATE DATABASE IF NOT EXISTS gestione_doposcuola;

USE gestione_doposcuola;

CREATE TABLE Genitori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100)
);

CREATE TABLE Alunni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    scuola VARCHAR(100),
    piano VARCHAR(50),
    quota FLOAT,
    ore INT,
    giorno_inizio DATE,
    stato VARCHAR(20) DEFAULT 'attivo', -- attivo, inattivo
    genitore_id INT,
    FOREIGN KEY (genitore_id) REFERENCES Genitori(id)
);



CREATE TABLE Pagamenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alunno_id INT,
    data_pagamento DATE NOT NULL,
    mese_pagamento VARCHAR(20) NOT NULL,
    metodo_pagamento VARCHAR(20), -- contanti, bonifico, assegno, carta
    importo FLOAT NOT NULL,
    tipo_pagamento VARCHAR(20), -- saldo, acconto
    note TEXT,
    FOREIGN KEY (alunno_id) REFERENCES Alunni(id)
);

CREATE TABLE OreEffettuate (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alunno_id INT,
    data DATE NOT NULL,
    ore INT NOT NULL,
    FOREIGN KEY (alunno_id) REFERENCES Alunni(id)
);

CREATE TABLE LogAzioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente VARCHAR(50),
    azione TEXT NOT NULL,
    data_azione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);