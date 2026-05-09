<?php
include "db-conn.php";
?>
<!DOCTYPE html>
<html lang="zxx">
<head>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Computer Electonics</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">

    <?php include "links.php"; ?>
</head>

<body class="crm_body_bg">

    <?php include "header.php"; ?>
    <section class="main_content dashboard_part large_header_bg">

        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "top_nav.php"; ?>
                </div>
            </div>
        </div>

        <div class="main_content_iner ">
            <div class="container-fluid p-0 sm_padding_15px">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col text-center">User Name</th>
                                    <th scope="col">Password</th>
                                    <th scope="col text-center">Edit</th>

                                    <th scope="col text-center">Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 0;
                                $sql = "SELECT * FROM `admin_user`";
                                $result = mysqli_query($conn, $sql);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    ?>
                                    <tr>
                                        <th scope="row"><?= $no++; ?></th>
                                        <td><?= $row['user'] ?></td>
                                        <td>***********</td>

                                        <td class=""><a href="edit_products.php?edit_product_details=
          "><i class="fa-regular fa-pen-to-square text-primary fs-3"></i></a></td>
                                        <form action="" method="post">
                                            <td class=""><a href="product_delete.php?delete="><i
                                                        class="fa-solid fa-trash text-danger fs-3"></i></a></td>
                                        </form>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>