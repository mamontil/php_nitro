use std::ffi::{CStr, CString};
use std::os::raw::c_char;
use std::fs::File;
use memmap2::MmapOptions;
use simd_json::prelude::*;

#[no_mangle]
pub extern "C" fn nitro_get_field(json_ptr: *const c_char, key_ptr: *const c_char) -> *mut c_char {
    if json_ptr.is_null() || key_ptr.is_null() { return std::ptr::null_mut(); }

    let json_bytes = unsafe { CStr::from_ptr(json_ptr) }.to_bytes();
    let key_str = unsafe { CStr::from_ptr(key_ptr) }.to_string_lossy();

    let mut buffer = json_bytes.to_vec();
    if let Ok(value) = simd_json::to_borrowed_value(&mut buffer) {
        return search_logic(&value, &key_str);
    }
    std::ptr::null_mut()
}

#[no_mangle]
pub extern "C" fn nitro_json_from_file(path_ptr: *const c_char, key_ptr: *const c_char) -> *mut c_char {
    if path_ptr.is_null() || key_ptr.is_null() { return std::ptr::null_mut(); }

    let path = unsafe { CStr::from_ptr(path_ptr) }.to_string_lossy();
    let key_str = unsafe { CStr::from_ptr(key_ptr) }.to_string_lossy();

    let file = match File::open(&*path) {
        Ok(f) => f,
        Err(_) => return std::ptr::null_mut(),
    };

    let mmap = unsafe { match MmapOptions::new().map(&file) {
        Ok(m) => m,
        Err(_) => return std::ptr::null_mut(),
    }};

    let mut buffer = mmap.to_vec();
    if let Ok(value) = simd_json::to_borrowed_value(&mut buffer) {
        return search_logic(&value, &key_str);
    }
    std::ptr::null_mut()
}

// Общая логика поиска, чтобы не дублировать код
fn search_logic(value: &simd_json::BorrowedValue, key: &str) -> *mut c_char {
    let parts: Vec<&str> = key.split('.').collect();
    let mut current = value;
    for part in parts {
        if let Some(next) = current.get(part) {
            current = next;
        } else {
            return std::ptr::null_mut();
        }
    }
    let out = match current {
        simd_json::BorrowedValue::String(s) => s.to_string(),
        _ => current.to_string(),
    };
    CString::new(out).unwrap().into_raw()
}

#[no_mangle]
pub extern "C" fn nitro_free_string(s: *mut c_char) {
    if !s.is_null() { unsafe { let _ = CString::from_raw(s); } }
}