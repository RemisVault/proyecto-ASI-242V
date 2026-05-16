CREATE OR REPLACE TRIGGER trg_ips_duplicadas
BEFORE INSERT OR UPDATE OF ip_primaria ON equipos
FOR EACH ROW
DECLARE
    v_existe NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_existe
    FROM equipos
    WHERE ip_primaria = :NEW.ip_primaria
        AND id_equipo != NVL(:NEW.id_equipo, -1);

    SELECT DECODE(v_existe, 0, 0, 1 / 0)
    INTO v_existe
    FROM DUAL;
    
EXCEPTION
    WHEN ZERO_DIVIDE THEN
        RAISE_APPLICATION_ERROR(-20001, 'Error: La dirección IP primaria está en uso.');
END;
/

CREATE OR REPLACE TRIGGER trg_macs_duplicadas
BEFORE INSERT OR UPDATE OF mac_primaria ON equipos
FOR EACH ROW
DECLARE
    v_existe NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_existe
    FROM equipos
    WHERE mac_primaria = :NEW.mac_primaria
        AND id_equipo != NVL(:NEW.id_equipo, -1);

    SELECT DECODE(v_existe, 0, 0, 1 / 0)
    INTO v_existe
    FROM DUAL;

EXCEPTION
    WHEN ZERO_DIVIDE THEN
        RAISE_APPLICATION_ERROR(-20002, 'Error: La dirección MAC ya está registrada en el sistema.');
END;
/

CREATE OR REPLACE TRIGGER trg_borrar_redes_con_equipos
BEFORE DELETE ON redes
FOR EACH ROW
DECLARE
    v_equipos_asociados NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_equipos_asociados
    FROM equipos
    WHERE id_red = :OLD.id_red;

    SELECT DECODE(v_equipos_asociados, 0, 0, 1 / 0)
    INTO v_equipos_asociados
    FROM DUAL;

EXCEPTION
    WHEN ZERO_DIVIDE THEN
        RAISE_APPLICATION_ERROR(-20003, 'Error: No se puede eliminar la red porque tiene equipos asignados.');
END;
/

CREATE OR REPLACE PROCEDURE prc_cambiar_red_equipo (
    p_hostname   IN equipos.hostname%TYPE,
    p_nombre_red IN redes.nombre_red%TYPE
) AS
    v_id_red    redes.id_red%TYPE;
    v_id_equipo equipos.id_equipo%TYPE;
BEGIN

    SELECT id_red INTO v_id_red FROM redes WHERE nombre_red = p_nombre_red;

    SELECT id_equipo INTO v_id_equipo FROM equipos WHERE hostname = p_hostname;

    UPDATE equipos
    SET id_red = v_id_red
    WHERE id_equipo = v_id_equipo;

EXCEPTION
    WHEN NO_DATA_FOUND THEN
        RAISE_APPLICATION_ERROR(-20005, 'Error: Operación cancelada. Compruebe que la red o el equipo existan.');
END;
/

CREATE OR REPLACE PROCEDURE prc_listar_equipos_red (
    p_nombre_red IN redes.nombre_red%TYPE,
    p_cursor     OUT SYS_REFCURSOR
) AS
    v_id_red redes.id_red%TYPE;
BEGIN

    SELECT id_red 
    INTO v_id_red
    FROM redes
    WHERE nombre_red = p_nombre_red;

    OPEN p_cursor FOR
    SELECT id_equipo, hostname, dominio, ip_primaria, mac_primaria
    FROM equipos
    WHERE id_red = v_id_red;

EXCEPTION
    WHEN NO_DATA_FOUND THEN
        RAISE_APPLICATION_ERROR(-20004, 'Error: La red especificada no existe.');
END;


CREATE OR REPLACE FUNCTION fun_contar_equipos_red (
    p_nombre_red IN redes.nombre_red%TYPE
) RETURN NUMBER AS
    v_id_red   redes.id_red%TYPE;
    v_total_eq NUMBER;
BEGIN
    SELECT id_red INTO v_id_red FROM redes WHERE nombre_red = p_nombre_red;

    SELECT COUNT(*)
    INTO v_total_eq
    FROM equipos
    WHERE id_red = v_id_red;

    RETURN v_total_eq;

EXCEPTION
    WHEN NO_DATA_FOUND THEN
        RAISE_APPLICATION_ERROR(-20006, 'Error: La red solicitada no existe.');
END;
/

CREATE OR REPLACE FUNCTION fun_obtener_so_equipo (
    p_hostname IN equipos.hostname%TYPE
) RETURN VARCHAR2 AS
    v_nombre    VARCHAR2(50);
    v_version   VARCHAR2(50);
BEGIN
    SELECT so.nombre, so.version
    INTO v_nombre, v_version
    FROM equipos eq
    JOIN sistemas_operativos so ON eq.id_so = so.id_so
    WHERE eq.hostname = p_hostname;

    RETURN v_nombre || ' ' || v_version;

EXCEPTION
    WHEN NO_DATA_FOUND THEN
        RAISE_APPLICATION_ERROR(-20007, 'Error: El equipo especificado no existe.');
END;

