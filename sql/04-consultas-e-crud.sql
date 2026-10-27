USE SportZone;
GO
-- Consultas de verificação e relatório (somente leitura).
SELECT c.name AS categoria,COUNT(p.id) AS produtos
FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.name;
SELECT id,name,price,sale_price,stock FROM products WHERE active=1 ORDER BY price;
SELECT o.number,u.name,o.status,o.total,p.method,p.status AS pagamento,p.simulated
FROM orders o JOIN users u ON u.id=o.user_id JOIN payments p ON p.order_id=o.id;
SELECT o.number,i.name,i.size,i.quantity,i.unit_price
FROM order_items i JOIN orders o ON o.id=i.order_id ORDER BY o.id;
SELECT id,name,stock FROM products WHERE active=1 AND stock<=5;
-- Invariantes: as próximas consultas devem retornar zero linhas.
SELECT id FROM orders WHERE total<>subtotal-discount+shipping;
SELECT id FROM products WHERE stock<0;
SELECT o.id FROM orders o LEFT JOIN payments p ON p.order_id=o.id WHERE p.id IS NULL;
GO
-- CRUD didático protegido por ROLLBACK: não altera o catálogo ao terminar.
BEGIN TRANSACTION;
DECLARE @category INT=(SELECT MIN(id) FROM categories WHERE active=1);
DECLARE @product INT;
INSERT INTO products(category_id,name,description,brand,gender,price,image,stock,sizes)
VALUES (@category,N'Produto SQL de teste',N'Demonstração CRUD da disciplina',N'SportZone','unissex',199.90,'assets/images/basquete.svg',10,'P,M,G');
SET @product=CONVERT(INT,SCOPE_IDENTITY());
SELECT * FROM products WHERE id=@product;
UPDATE products SET price=179.90,stock=12 WHERE id=@product;
SELECT id,name,price,stock FROM products WHERE id=@product;
DELETE FROM products WHERE id=@product;
ROLLBACK TRANSACTION;
GO
