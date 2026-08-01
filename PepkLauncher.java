import java.security.Provider;
import java.security.Security;
import java.security.AlgorithmParameters;
import java.security.InvalidAlgorithmParameterException;
import java.security.InvalidKeyException;
import java.security.Key;
import java.security.NoSuchAlgorithmException;
import java.security.SecureRandom;
import java.security.spec.AlgorithmParameterSpec;
import javax.crypto.Cipher;
import javax.crypto.CipherSpi;
import javax.crypto.IllegalBlockSizeException;
import javax.crypto.BadPaddingException;
import javax.crypto.NoSuchPaddingException;
import javax.crypto.ShortBufferException;

public final class PepkLauncher {
  private PepkLauncher() {}

  public static void main(String[] args) throws Exception {
    Provider provider = new org.bouncycastle.jce.provider.BouncyCastleProvider();
    provider.put("Alg.Alias.Cipher.RSA/NONE/OAEPWithSHA1AndMGF1Padding", "RSA/OAEP");
    provider.put("Alg.Alias.Cipher.RSA/NONE/OAEPWITHSHA1ANDMGF1PADDING", "RSA/OAEP");
    Security.insertProviderAt(new PepkAliasProvider(), 1);
    Security.insertProviderAt(provider, 2);
    if (Boolean.getBoolean("pepk.debugCipher")) {
      for (String transformation :
          new String[] {
            "RSA/NONE/OAEPWithSHA1AndMGF1Padding",
            "RSA/ECB/OAEPWithSHA-1AndMGF1Padding",
            "RSA/OAEP",
            "RSA"
          }) {
        try {
          Cipher cipher = Cipher.getInstance(transformation);
          System.out.println(transformation + " => " + cipher.getProvider().getName());
        } catch (Exception e) {
          System.out.println(transformation + " => " + e.getClass().getSimpleName());
        }
      }
      try {
        Cipher cipher = Cipher.getInstance("RSA", "PepkAlias");
        System.out.println("RSA@PepkAlias => " + cipher.getProvider().getName());
      } catch (Exception e) {
        System.out.println("RSA@PepkAlias => " + e);
      }
      return;
    }
    com.google.wireless.android.vending.developer.signing.tools.extern.export.ExportEncryptedPrivateKeyTool.main(args);
  }

  private static final class PepkAliasProvider extends Provider {
    PepkAliasProvider() {
      super("PepkAlias", "1.0", "Alias provider for PEPK RSA OAEP transformation");
      put(
          "Cipher.RSA",
          "PepkLauncher$RsaOaepSha1CipherSpi");
      put("Cipher.RSA SupportedModes", "NONE");
      put("Cipher.RSA SupportedPaddings", "OAEPWITHSHA1ANDMGF1PADDING|OAEPWITHSHA-1ANDMGF1PADDING");
    }
  }

  public static final class RsaOaepSha1CipherSpi extends CipherSpi {
    private final Cipher delegate;

    public RsaOaepSha1CipherSpi() throws NoSuchAlgorithmException, NoSuchPaddingException {
      delegate = Cipher.getInstance("RSA/ECB/OAEPWithSHA-1AndMGF1Padding");
    }

    @Override
    protected void engineSetMode(String mode) {}

    @Override
    protected void engineSetPadding(String padding) {}

    @Override
    protected int engineGetBlockSize() {
      return delegate.getBlockSize();
    }

    @Override
    protected int engineGetOutputSize(int inputLen) {
      return delegate.getOutputSize(inputLen);
    }

    @Override
    protected byte[] engineGetIV() {
      return delegate.getIV();
    }

    @Override
    protected AlgorithmParameters engineGetParameters() {
      return delegate.getParameters();
    }

    @Override
    protected void engineInit(int opmode, Key key, SecureRandom random) throws InvalidKeyException {
      delegate.init(opmode, key, random);
    }

    @Override
    protected void engineInit(int opmode, Key key, AlgorithmParameterSpec params, SecureRandom random)
        throws InvalidKeyException, InvalidAlgorithmParameterException {
      delegate.init(opmode, key, params, random);
    }

    @Override
    protected void engineInit(int opmode, Key key, AlgorithmParameters params, SecureRandom random)
        throws InvalidKeyException, InvalidAlgorithmParameterException {
      delegate.init(opmode, key, params, random);
    }

    @Override
    protected byte[] engineUpdate(byte[] input, int inputOffset, int inputLen) {
      return delegate.update(input, inputOffset, inputLen);
    }

    @Override
    protected int engineUpdate(byte[] input, int inputOffset, int inputLen, byte[] output, int outputOffset)
        throws ShortBufferException {
      return delegate.update(input, inputOffset, inputLen, output, outputOffset);
    }

    @Override
    protected byte[] engineDoFinal(byte[] input, int inputOffset, int inputLen)
        throws IllegalBlockSizeException, BadPaddingException {
      return delegate.doFinal(input, inputOffset, inputLen);
    }

    @Override
    protected int engineDoFinal(byte[] input, int inputOffset, int inputLen, byte[] output, int outputOffset)
        throws ShortBufferException, IllegalBlockSizeException, BadPaddingException {
      return delegate.doFinal(input, inputOffset, inputLen, output, outputOffset);
    }

    @Override
    protected byte[] engineWrap(Key key) throws IllegalBlockSizeException, InvalidKeyException {
      return delegate.wrap(key);
    }

    @Override
    protected Key engineUnwrap(byte[] wrappedKey, String wrappedKeyAlgorithm, int wrappedKeyType)
        throws InvalidKeyException, NoSuchAlgorithmException {
      return delegate.unwrap(wrappedKey, wrappedKeyAlgorithm, wrappedKeyType);
    }
  }
}
