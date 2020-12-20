INSERT INTO aircraft VALUES ('1', '1', 'OY-BLZ', 'PA28-151 Warrior', '691', '2.2', '1056', '.', '2.108', '2.362', 'Gallons');

INSERT INTO aircraft_cg VALUES ('1', '1', '2.108', '885');
INSERT INTO aircraft_cg VALUES ('2', '1', '2.21', '1056');
INSERT INTO aircraft_cg VALUES ('3', '1', '2.362', '1056');
INSERT INTO aircraft_cg VALUES ('4', '1', '2.362', '691');
INSERT INTO aircraft_cg VALUES ('5', '1', '2.108', '691');

INSERT INTO aircraft_weights VALUES('1','1','1','Empty weight','691','2.2','true','false',NULL);
INSERT INTO aircraft_weights VALUES('2','1','2','Pilot','0','2.045','false','false',NULL);
INSERT INTO aircraft_weights VALUES('3','1','3','Front seat','0','2.045','false','false',NULL);
INSERT INTO aircraft_weights VALUES('4','1','4','Rear seat 1','0','3','false','false',NULL);
INSERT INTO aircraft_weights VALUES('5','1','5','Rear seat 2','0','3','false','false',NULL);
INSERT INTO aircraft_weights VALUES('6','1','6','Baggage','0','3.63','false','false',NULL);
INSERT INTO aircraft_weights VALUES('7','1','7','Fuel','0','2.41','false','true','2.72');
