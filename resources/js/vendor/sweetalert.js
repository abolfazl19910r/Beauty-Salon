// SweetAlert2 از npm (قبلاً jsDelivr): روی window تا اسکریپت‌های درون‌خطی Blade همان Swal.fire را صدا بزنند.
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Swal = Swal;
