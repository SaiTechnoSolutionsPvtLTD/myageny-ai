@if(session('success'))
<div class="crm-alert crm-alert-success">
    <span>Success: {{ session('success') }}</span>
    <button type="button" onclick="this.parentElement.remove()">x</button>
</div>
@endif

@if(session('error'))
<div class="crm-alert crm-alert-error">
    <span>Error: {{ session('error') }}</span>
    <button type="button" onclick="this.parentElement.remove()">x</button>
</div>
@endif

@if(isset($errors) && $errors->any())
<div class="crm-alert crm-alert-error">
    <span>Error: {{ $errors->first() }}</span>
    <button type="button" onclick="this.parentElement.remove()">x</button>
</div>
@endif

<style>
.crm-alert {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    border-radius: 10px;
    margin-bottom: 16px;
    font-size: 14px;
    font-weight: 500;
    gap: 12px;
}

.crm-alert-success {
    background: #f1fdf6;
    border: 1px solid #c3edd8;
    color: #225247;
}

.crm-alert-error {
    background: #fff4f4;
    border: 1px solid #ffc9c9;
    color: #a00;
}

.crm-alert button {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
    opacity: .6;
}
</style>
