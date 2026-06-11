<script>
window.exclusiveDropdownMixin = function (id) {
    return {
        _dropdownId: id,
        open: false,
        _dropdownInit() {
            window.addEventListener('exclusive-dropdown-open', (e) => {
                if (e.detail !== this._dropdownId) {
                    this.open = false;
                }
            });
        },
        toggleDropdown() {
            if (this.open) {
                this.open = false;
                return;
            }
            window.dispatchEvent(new CustomEvent('exclusive-dropdown-open', { detail: this._dropdownId }));
            this.open = true;
        },
        closeDropdown() {
            this.open = false;
        },
    };
};
</script>
