 
(function (global) {
  'use strict';

  const brandConfirm = '#2c3e50';
  const brandAccent = '#f1c40f';

  function notifySuccess(title, text, timer) {
    return Swal.fire({
      icon: 'success',
      title: title || 'Success',
      text: text || '',
      timer: timer == null ? 2200 : timer,
      showConfirmButton: timer === 0,
      confirmButtonColor: brandConfirm,
    });
  }

  function notifyError(title, text) {
    return Swal.fire({
      icon: 'error',
      title: title || 'Error',
      text: text || 'Something went wrong.',
      confirmButtonColor: brandConfirm,
    });
  }

  function notifyWarning(title, text) {
    return Swal.fire({
      icon: 'warning',
      title: title || 'Notice',
      text: text || '',
      confirmButtonColor: brandConfirm,
    });
  }

  /**
   * @returns {Promise<boolean>}  
   */
  function confirmAction(options) {
    const o = options || {};
    return Swal.fire({
      title: o.title || 'Are you sure?',
      text: o.text || '',
      icon: o.icon || 'question',
      showCancelButton: true,
      confirmButtonColor: o.danger ? '#c0392b' : brandConfirm,
      cancelButtonColor: '#95a5a6',
      confirmButtonText: o.confirmText || 'Yes',
      cancelButtonText: o.cancelText || 'Cancel',
    }).then(function (r) {
      return !!r.isConfirmed;
    });
  }

  global.LaundryNotify = {
    success: notifySuccess,
    error: notifyError,
    warning: notifyWarning,
    confirm: confirmAction,
    colors: { primary: brandConfirm, accent: brandAccent },
  };
})(typeof window !== 'undefined' ? window : this);
