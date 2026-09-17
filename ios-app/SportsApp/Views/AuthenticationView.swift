import SwiftUI

struct AuthenticationView: View {
    @EnvironmentObject private var session: SessionStore
    @Environment(\.dismiss) private var dismiss
    @State private var mode = 0
    @State private var name = ""
    @State private var email = ""
    @State private var password = ""
    @State private var phone = ""
    @State private var isLoading = false
    @State private var errorMessage: String?

    var body: some View {
        Form {
            Picker("會員功能", selection: $mode) {
                Text("登入").tag(0)
                Text("註冊").tag(1)
            }
            .pickerStyle(.segmented)

            Section(mode == 0 ? "會員登入" : "建立會員") {
                if mode == 1 {
                    TextField("姓名", text: $name).textContentType(.name)
                    TextField("電話（選填）", text: $phone).textContentType(.telephoneNumber).keyboardType(.phonePad)
                }
                TextField("Email", text: $email)
                    .textContentType(.emailAddress).keyboardType(.emailAddress)
                    .textInputAutocapitalization(.never).autocorrectionDisabled()
                SecureField("密碼（至少 8 個字元）", text: $password).textContentType(mode == 0 ? .password : .newPassword)
            }

            if let errorMessage {
                Section { Text(errorMessage).foregroundStyle(.red).font(.footnote) }
            }

            Section {
                Button(mode == 0 ? "登入" : "註冊並登入") { Task { await submit() } }
                    .frame(maxWidth: .infinity)
                    .disabled(!isValid || isLoading)
            }
        }
        .navigationTitle(mode == 0 ? "會員登入" : "會員註冊")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar {
            ToolbarItem(placement: .cancellationAction) { Button("關閉") { dismiss() } }
        }
        .loading(isLoading)
    }

    private var isValid: Bool {
        email.contains("@") && password.count >= 8 && (mode == 0 || !name.trimmingCharacters(in: .whitespaces).isEmpty)
    }

    private func submit() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            if mode == 0 {
                try await session.login(email: email, password: password)
            } else {
                try await session.register(name: name, email: email, password: password, phone: phone)
            }
            dismiss()
        } catch { errorMessage = error.localizedDescription }
    }
}

