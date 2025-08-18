-- Seed sample zones, slots and edges

INSERT INTO zones (name, x, y) VALUES
 ('Zone A', 50, 50),
 ('Zone B', 250, 50);

-- Create 10 slots per zone (5x2 grid per zone)
INSERT INTO slots (zone_id, name, x, y, status)
SELECT z.id, CONCAT(z.name, ' - S', n.seq),
       CASE WHEN n.seq <= 5 THEN z.x + (n.seq-1)*30 ELSE z.x + (n.seq-6)*30 END,
       CASE WHEN n.seq <= 5 THEN z.y ELSE z.y + 40 END,
       'available'
FROM zones z
JOIN (
  SELECT 1 AS seq UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
  UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
) n;

-- Simple grid edges within each zone (horizontal and vertical neighbors)
INSERT INTO edges (from_slot_id, to_slot_id, weight)
SELECT s1.id, s2.id, 30.0
FROM slots s1
JOIN slots s2 ON s1.zone_id = s2.zone_id AND s1.id <> s2.id
WHERE (
  (ABS(s1.x - s2.x) = 30 AND s1.y = s2.y) OR
  (ABS(s1.y - s2.y) = 40 AND s1.x = s2.x)
);

-- Mirror edges to make the graph undirected (if not already present)
INSERT INTO edges (from_slot_id, to_slot_id, weight)
SELECT e.to_slot_id, e.from_slot_id, e.weight
FROM edges e
LEFT JOIN edges e2 ON e2.from_slot_id = e.to_slot_id AND e2.to_slot_id = e.from_slot_id
WHERE e2.id IS NULL;