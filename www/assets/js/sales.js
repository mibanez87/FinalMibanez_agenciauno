document.addEventListener("DOMContentLoaded", function () {
    // Inicializar Select2 para los campos de Clientes y producto
    $('#id_customer').select2({
        placeholder: "Seleccione un Cliente",
        allowClear: true,
        width: '100%'
    });

    $('#id_product').select2({
        placeholder: "Seleccione un Producto",
        allowClear: true,
        width: '100%'
    });
    // Manejo de mensajes guardados en el almacenamiento local (para mostrar con SweetAlert)
    if (localStorage.getItem('mensaje') && localStorage.getItem('tipo')) {
        Swal.fire({
            title: 'Mensaje',
            text: localStorage.getItem('mensaje'),
            icon: localStorage.getItem('tipo'),
            confirmButtonText: 'Aceptar'
        });

        // Limpiar el mensaje después de mostrarlo
        localStorage.removeItem('mensaje');
        localStorage.removeItem('tipo');
    }
         // Detectar cambios en el select de clientes
         $('#id_customer').on('change', function() {
            var idCustomer = $(this).val();

            if (idCustomer) {
                // Enviar la solicitud AJAX al servidor
                $.ajax({
                    url: '../controller/get_customer.php',
                    type: 'POST',
                    data: {
                        id_customer: idCustomer
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.error) {
                            alert(data.error);
                        } else {
                            // Actualizar los campos con la información recibida
                            $('#view_tax').text(data.tax_identifier || '');
                            $('#view_email').text(data.email_customer || '');
                            $('#view_phone').text(data.phone_customer || '');                            
                        }
                    },
                    error: function() {
                        alert('Error al obtener la información del cliente.');
                    }
                });
            } else {
                // Limpiar los campos si no hay cliente seleccionado
                $('#view_tax').text('');
                $('#view_email').text('');
                $('#view_phone').text('');
            }
        });

    let productCounter = 0;
    const table = $('#table_products').DataTable();    
    // Manejo del evento click en el botón para agregar el producto a la tabla
    $('#addProduct').on('click', function() {
        const productId = $('#id_product').val();
        const productName = $('#id_product option:selected').text().trim();
        const quantity = $('#quantity_input').val();
        const customerId = $('#id_customer').val();
    
        // Verificar si se seleccionó un producto, se ingresó una cantidad y un cliente
        if (productId && quantity > 0 && customerId) {
            let productExists = false;    
            // Verificar si el producto ya ha sido agregado a la tabla
            $('#table_products tbody tr').each(function() {
                const existingProductId = $(this).find('input[name^="items"][name$="[id_product]"]').val();
                if (existingProductId === productId) {
                    productExists = true;
                    return false; // Salir del loop si el producto ya existe
                }
            });
    
            if (productExists) {
                Swal.fire('Error', 'El producto ya ha sido agregado.', 'error');
            } else {
                // Agregar la nueva fila a la tabla con los datos seleccionados
                table.row.add([
                    `<input type="hidden" name="items[${productCounter}][id_product]" value="${productId}">${productCounter+1}`,
                    `<input type="hidden" name="items[${productCounter}][name_product]" value="${productName}">${productName}`,
                    `<input type="hidden" name="items[${productCounter}][quantity]" value="${quantity}">${quantity}`,
                    `<button type="button" class="delete-row"><i class="fas fa-trash-alt"></i></button>`
                ]).draw();
    
                // Incrementar el contador de productos
                productCounter++;    
                // Limpiar los campos para la próxima entrada
                $('#id_product').val('').trigger('change'); // Limpiar selección de producto
                $('#quantity_input').val(''); // Limpiar el campo de cantidad
    
                // Manejar la lógica del Cliente
                $('#id_customer').prop('disabled', true);                
    
                // Si no existe el campo oculto de Cliente, agregarlo
                if (!$('input[name="id_customer"]').length) {
                    $('<input>').attr({
                        type: 'hidden',
                        id: 'hidden_id_customer',
                        name: 'id_customer',
                        value: customerId
                    }).appendTo('form');
                }
            }
        } else {
            Swal.fire('Error', 'Debe seleccionar un producto, un cliente y una cantidad válida.', 'error');
        }
    });

// Manejar el clic en el botón de eliminar fila
$('#table_products tbody').on('click', '.delete-row', function (e) {
    e.preventDefault(); // Evita la acción por defecto del enlace o botón
    const row = table.row($(this).closest('tr')); // La fila que se va a eliminar

    Swal.fire({
        title: '¿Estás seguro?',
        text: "¿Estás seguro de que deseas eliminar este producto?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Eliminar la fila solo si el usuario confirma
            row.remove().draw();

            // Si no hay filas restantes, permitir la selección del cliente nuevamente
            if (table.rows().count() === 0) {
                $('#id_supplier').prop('disabled', false);
                enableProductEditing(); // Habilitar edición de producto y cantidad nuevamente
            }

            Swal.fire(
                'Eliminado',
                'El producto ha sido eliminado.',
                'success'
            );
        }
    });
});

});
$(document).ready(function() {
  
    $('#id_product').on('change', function() {
        var selectedProduct = $(this).find('option:selected');
        var description = selectedProduct.data('description');
        var stock = selectedProduct.data('stock');
        var productInfo = "Descripción: " + description + " | Stock Disponible: " + stock;
        document.getElementById('product_info').value = productInfo;
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('id_product');
    const productInfo = document.getElementById('product_info');

    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const description = selectedOption.getAttribute('data-description');
        const stock = selectedOption.getAttribute('data-stock');

        if (selectedOption.value) {
            productInfo.value = `Descripción: ${description} | Stock: ${stock}`;
        } else {
            productInfo.value = '';
        }
    });
});
$(document).ready(function() {
    $('#id_product').change(function() {
        // Obtener el stock y descripción del producto seleccionado
        let selectedProduct = $(this).find(':selected');
        let stock = selectedProduct.data('stock');
        let description = selectedProduct.data('description');

        // Mostrar la descripción del producto
        $('#product_info').val(description);

        // Establecer el valor máximo permitido en el input de cantidad
        $('#quantity_input').attr('max', stock);

        // Limpiar el campo de cantidad
        $('#quantity_input').val('');
    });

    $('#quantity_input').on('input', function() {
        // Obtener el valor ingresado y el stock máximo permitido
        let quantity = parseInt($(this).val());
        let maxQuantity = parseInt($(this).attr('max'));

        // Verificar si la cantidad es mayor al stock disponible
        if (quantity > maxQuantity) {
            Swal.fire({
                icon: 'error',
                title: 'Cantidad inválida',
                text: 'La cantidad no puede ser mayor al stock disponible (' + maxQuantity + ').',
            });
            $(this).val(maxQuantity);  // Ajustar la cantidad al máximo disponible
        }
    });
});



