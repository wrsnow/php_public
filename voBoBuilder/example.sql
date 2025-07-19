CREATE TABLE `produto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `categorias` json NOT NULL,
  `criado_em` datetime NOT NULL,
  `criado_por` int NOT NULL,
  `deletado_em` datetime DEFAULT NULL,
  `deletado_por` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;