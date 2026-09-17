import java.util.Properties

plugins {
    id("com.android.application")
}

val releaseSigningProperties = Properties()
val releaseSigningFile = rootProject.file("keystore.properties")
if (releaseSigningFile.exists()) {
    releaseSigningFile.inputStream().use(releaseSigningProperties::load)
}

android {
    namespace = "com.example.sportsnews"
    compileSdk = 36

    defaultConfig {
        applicationId = "com.example.sportsnews"
        minSdk = 26
        targetSdk = 36
        versionCode = 6
        versionName = "1.2.1"

        buildConfigField("String", "API_BASE_URL", "\"https://purple-oyster-440244.hostingersite.com/api/v1/\"")
    }

    signingConfigs {
        if (releaseSigningFile.exists()) {
            create("release") {
                storeFile = rootProject.file(releaseSigningProperties.getProperty("storeFile"))
                storePassword = releaseSigningProperties.getProperty("storePassword")
                keyAlias = releaseSigningProperties.getProperty("keyAlias")
                keyPassword = releaseSigningProperties.getProperty("keyPassword")
            }
        }
    }

    buildTypes {
        release {
            isMinifyEnabled = false
            signingConfig = signingConfigs.findByName("release")
            proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    buildFeatures {
        buildConfig = true
    }
}
