
/*Ко времени прибавлять 4 часа */

SELECT *
FROM session
ORDER BY created_at DESC;


SELECT *
FROM hit
WHERE user_id IS NOT NULL
    AND user_id NOT IN (1, 3, 4, 7, 10, 16)
ORDER BY created_at DESC;


SELECT count(*)
FROM master_profile
WHERE DATE(master_profile.created_at) = current_date()
UNION ALL
SELECT count(*)
FROM salon_profile
WHERE DATE(salon_profile.created_at) = current_date()


SELECT count(*)
FROM master_profile
WHERE user_id NOT IN (1, 3, 4, 7, 10, 16)
UNION ALL
SELECT count(*)
FROM salon_profile
WHERE user_id NOT IN (1, 3, 4, 7, 10, 16)