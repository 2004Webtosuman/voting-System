-- Seed data for Smart Parking (no default users; first registered becomes admin)

INSERT INTO zones (name, description) VALUES ('Zone A', 'Main lot'), ('Zone B', 'Overflow lot');

-- Entrances
INSERT INTO entrances (zone_id, name) VALUES
(1, 'North Gate'), (1, 'South Gate'),
(2, 'East Gate');

-- Slots (Zone A: 24 slots; Zone B: 12 slots)
INSERT INTO slots (zone_id, label, is_available, x, y) VALUES
(1, 'A1', 1, 1, 1), (1, 'A2', 1, 2, 1), (1, 'A3', 1, 3, 1), (1, 'A4', 1, 4, 1),
(1, 'A5', 1, 5, 1), (1, 'A6', 1, 6, 1), (1, 'A7', 1, 7, 1), (1, 'A8', 1, 8, 1),
(1, 'A9', 1, 9, 1), (1, 'A10',1,10, 1), (1, 'A11',1,11, 1), (1, 'A12',1,12, 1),
(1, 'A13',1, 1, 2), (1, 'A14',1, 2, 2), (1, 'A15',1, 3, 2), (1, 'A16',1, 4, 2),
(1, 'A17',1, 5, 2), (1, 'A18',1, 6, 2), (1, 'A19',1, 7, 2), (1, 'A20',1, 8, 2),
(1, 'A21',1, 9, 2), (1, 'A22',1,10, 2), (1, 'A23',1,11, 2), (1, 'A24',1,12, 2),
(2, 'B1', 1, 1, 1), (2, 'B2', 1, 2, 1), (2, 'B3', 1, 3, 1), (2, 'B4', 1, 4, 1),
(2, 'B5', 1, 1, 2), (2, 'B6', 1, 2, 2), (2, 'B7', 1, 3, 2), (2, 'B8', 1, 4, 2),
(2, 'B9', 1, 1, 3), (2, 'B10',1, 2, 3), (2, 'B11',1, 3, 3), (2, 'B12',1, 4, 3);

-- Simple complete graph edges from entrances to each slot with weight approximated by Manhattan distance
INSERT INTO graph_edges (zone_id, from_node_type, from_node_id, to_node_type, to_node_id, weight)
SELECT 1, 'entrance', e.id, 'slot', s.id, ABS(s.x - 1) + ABS(s.y - 1) + 1
FROM entrances e, slots s WHERE e.zone_id = 1 AND s.zone_id = 1;

INSERT INTO graph_edges (zone_id, from_node_type, from_node_id, to_node_type, to_node_id, weight)
SELECT 2, 'entrance', e.id, 'slot', s.id, ABS(s.x - 1) + ABS(s.y - 1) + 1
FROM entrances e, slots s WHERE e.zone_id = 2 AND s.zone_id = 2;

-- Seed recent 48 hours using recursive CTE (MySQL 8+)
WITH RECURSIVE hours_ago AS (
  SELECT 0 AS h
  UNION ALL
  SELECT h + 1 FROM hours_ago WHERE h < 47
)
INSERT INTO zone_hourly_occupancy (zone_id, hh, occupied)
SELECT 1, DATE_SUB(NOW(), INTERVAL h HOUR), 10 + (h % 10)
FROM hours_ago;

